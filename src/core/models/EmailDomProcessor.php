<?php
  /**
   * Třída zajišťující zpracování HTML e-mailu na úrovni DOM včetně
   * zpracování a sanitizace HTML, případného obalení elementů ve formě
   * přidání zvýraznění indicií, proměnných nebo odkazů.
   *
   * @author Martin Šebela
   */
  class EmailDomProcessor {
    /**
     * Vrátí tělo HTML e-mailu zprocované pro výpis včetně sanitizace a případného vyznačení indicií, proměnných a odkazů.
     *
     * @param string $body             Tělo e-mailu
     * @param array $indications       Pole obsahující všechny indicie, které mají být v těle e-mailu zvýrazněny
     * @param bool $highlightVariables TRUE, pokud má dojít ke zvýraznění proměnných, jinak FALSE (výchozí)
     * @return false|string            Zpracované tělo e-mailu
     * @throws DOMException
     */
    public static function processEmailBodyHtml($body, $indications, $highlightVariables = false) {
      $dom = self::loadDom($body);

      self::sanitizeDom($dom);
      self::applyTextIndications($dom, $indications);

      if ($highlightVariables) {
        self::applyVariableHighlighting($dom);
      }

      self::applyLinksHighlighting($dom, $indications, $highlightVariables);

      return $dom->saveHTML($dom->getElementById('root'));
    }


    /**
     * Vrátí seznam povolených HTML tagů a povolené atributy u těchto tagů.
     *
     * @return array                   Pole s povolenými tagy (klíče) a povolenými atributy (hodnoty)
     */
    private static function getAllowedHtmlTags() {
      return [
        'a'          => ['href', 'target', 'rel', 'class', 'style'],
        'b'          => [],
        'strong'     => [],
        'i'          => [],
        'em'         => [],
        'u'          => [],
        's'          => [],
        'br'         => [],
        'p'          => [],
        'ul'         => [],
        'ol'         => [],
        'li'         => [],
        'table'      => ['width', 'cellpadding', 'cellspacing', 'border', 'style'],
        'tbody'      => [],
        'thead'      => [],
        'tr'         => [],
        'td'         => ['colspan', 'rowspan', 'width', 'align', 'style'],
        'th'         => ['colspan', 'rowspan', 'width', 'align', 'style'],
        'hr'         => [],
        'blockquote' => ['style'],
        'span'       => ['class', 'data-url', 'style']
      ];
    }


    /**
     * Vrátí seznam povolených CSS vlastností u inline style atributů.
     *
     * @return array                   Pole povolených CSS vlastností
     */
    private static function getAllowedCssProperties() {
      return [
        'color',
        'background-color',
        'text-align',
        'font-weight'
      ];
    }


    /**
     * Načte uživatelský HTML fragment pro další zpracování.
     *
     * @param string $html             HTML fragment
     * @return DOMDocument             Dokument pro další zpracování
     */
    public static function loadDom($html) {
      $dom = new DOMDocument('1.0', 'UTF-8');

      // Vypnutí výpisů s varováním ohledně nevalidního uživatelského HTML.
      libxml_use_internal_errors(true);

      $dom->loadHTML(
        '<meta charset="utf-8"><div id="root">' . $html . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
      );

      libxml_clear_errors();

      return $dom;
    }


    /**
     * Zpracuje DOM a odstraní z HTML fragmentu všechny nepovolené HTML tagy,
     * nepovolené atributy těchto tagů a nepovolené CSS vlastnosti.
     *
     * @param DOMDocument $dom         Dokument k sanitizování
     * @return void
     */
    private static function sanitizeDom($dom) {
      $allowedTags = self::getAllowedHtmlTags();
      $allowedCssProperties = self::getAllowedCssProperties();

      $xpath = new DOMXPath($dom);

      // Průchod přes všechy HTML tagy.
      foreach ($xpath->query('//*') as $node) {
        $tag = mb_strtolower($node->nodeName);

        // Ověření, zdali je použitý HTML tag povolený a jeho případné odstranění.
        if (!array_key_exists($tag, $allowedTags)) {
          $parent = $node->parentNode;

          while ($node->firstChild) {
            $parent->insertBefore($node->firstChild, $node);
          }

          $parent->removeChild($node);

          continue;
        }

        // Ověření, zdali HTML tag obsahuje pouze povolené atributy a jejich případné odstranění.
        if ($node->hasAttributes()) {
          foreach (iterator_to_array($node->attributes) as $attribute) {
            $attributeName = mb_strtolower($attribute->nodeName);
            $attributeValue = $attribute->nodeValue;

            // Odstranění nepovoleného atributu u kontrolovaného HTML tagu.
            if (!in_array($attributeName, $allowedTags[$tag], true)) {
              $node->removeAttribute($attributeName);
              continue;
            }

            // Kontrola odkazů u HTML tagu a atributu "<a href>".
            if ($tag === 'a' && $attributeName === 'href' && !self::isSafeHref($attributeValue)) {
              $node->removeAttribute('href');
            }

            // Kontrola použitých CSS vlastností u atributu "style".
            if ($attributeName === 'style') {
              $cssProperties = explode(';', $attributeValue);
              $cleanCssProperties = [];

              foreach ($cssProperties as $cssPair) {
                if (!trim($cssPair)) {
                  continue;
                }

                [$property, $value] = array_map('trim', explode(':', $cssPair, 2));

                if (in_array(mb_strtolower($property), $allowedCssProperties, true)) {
                  $cleanCssProperties[] = $property . ': ' . $value;
                }
              }

              if ($cleanCssProperties) {
                $node->setAttribute('style', implode('; ', $cleanCssProperties));
              }
              else {
                $node->removeAttribute('style');
              }
            }
          }
        }
      }
    }


    /**
     * Vrátí, zda je daný uzel uvnitř elementu, do kterého by se nemělo zasahovat
     * (např. při vkládání označení indicií, proměnných nebo odkazů).
     *
     * @param DOMNode $node            Kontrolovaný uzel
     * @return bool                    TRUE, pokud se nemá do uzlu zasahovat, jinak FALSE
     */
    private static function isInsideProtectedNode($node) {
      $allowedTags = self::getAllowedHtmlTags();

      while ($node !== null) {
        if ($node instanceof DOMElement) {
          $tag = strtolower($node->tagName);

          // Ověření, zdali jde o tag, který je povolen.
          if (!isset($allowedTags[$tag])) {
            return true;
          }

          // Speciální pravidlo pro již zpracované odkazy.
          if ($node->hasAttribute('class') && str_contains($node->getAttribute('class'), 'indication-link')) {
            return true;
          }

          // Speciální pravidlo pro odkazy s indiciemi, kam je povolené zasahovat (pro vložení tooltip komponenty aj.).
          if ($tag === 'a') {
            $isIndication = $node->hasAttribute('class') && str_contains($node->getAttribute('class'), 'indication');

            if (!$isIndication) {
              return true;
            }
          }
        }

        $node = $node->parentNode;
      }

      return false;
    }


    /**
     * Určuje, zda se daný uzel nachází v kontextu, kde není bezpečné provádět zásahy
     * (např. vkládání označení indicií, proměnných nebo odkazů).
     *
     * @param DOMNode $node            Kontrolovaný uzel
     * @return bool                    TRUE, pokud je uzel v zakázaném kontextu, jinak FALSE
     */
    public static function isNodeInRestrictedContext($node) {
      $allowedTags = self::getAllowedHtmlTags();

      while ($node !== null) {
        if ($node instanceof DOMElement) {
          $tag = strtolower($node->tagName);
          $class = $node->getAttribute('class') ?? '';

          // Nepovolený HTML tag.
          if (!isset($allowedTags[$tag])) {
            return true;
          }

          // Už zpracovaný odkaz (tooltip wrapper apod.)
          if (str_contains($class, 'indication-link') || str_contains($class, 'email-variable')) {
            return true;
          }

          // Běžný odkaz.
          if ($tag === 'a') {
            $isIndication = str_contains($class, 'indication');

            // Zakázat, pokud nejde o indicii nebo o proměnno pro podvodný odkaz.
            if (!$isIndication) {
              return true;
            }
          }
        }

        $node = $node->parentNode;
      }

      return false;
    }


    /**
     * Ověří, zdali atribut href u HTML tagu <a> používá protokol HTTP(S).
     *
     * @param string $hrefValue        Hodnota atributu "href" u tagu <a>
     * @return bool                    TRUE, pokud byl v atributu tagu použit HTTP(S) protokol, jinak FALSE
     */
    private static function isSafeHref($hrefValue) {
      if ($hrefValue == VAR_URL || $hrefValue == VAR_URL_PREVIEW) {
        return true;
      }

      $hrefValue = Controller::decodeHtmlEntities($hrefValue);
      $normalizedHrefValue = preg_replace('/\s+/', '', mb_strtolower(trim($hrefValue)));

      return str_starts_with($normalizedHrefValue, 'http://') || str_starts_with($normalizedHrefValue, 'https://');
    }


    /**
     * Vyhledá zadaný text a nahradí jeho výskyty obaleným elementem.
     *
     * @param DOMDocument $dom         Dokument, ve kterém se bude nahrazovat
     * @param string $expression       Text, který se má vyhledat a obalit
     * @param callable $wrapperFactory Callback, který vytvoří obalový element pro nalezený text
     * @return void
     */
    private static function wrapExpression($dom, $expression, $wrapperFactory) {
      $xpath = new DOMXPath($dom);
      $textNodes = iterator_to_array($xpath->query('//text()'));

      foreach ($textNodes as $textNode) {
        $text = $textNode->nodeValue;

        if (!str_contains($text, $expression)) {
          continue;
        }

        // Ověření, zdali uzel není v zakázaném kontextu (pak nezasahovat).
        if (self::isNodeInRestrictedContext($textNode->parentNode)) {
          continue;
        }

        $parent = $textNode->parentNode;
        $offset = 0;

        // Procházení všech výskytů daného výrazu v textu.
        while (($pos = strpos($text, $expression, $offset)) !== false) {
          // Vložení textu před nalezeným výrazem zpět jako textový uzel.
          if ($pos > $offset) {
            $parent->insertBefore(
              $dom->createTextNode(substr($text, $offset, $pos - $offset)),
              $textNode
            );
          }

          // Vytvoření obalového elementu.
          $wrapper = $wrapperFactory($dom, $expression);
          $parent->insertBefore($wrapper, $textNode);

          // Posunutí offsetu za aktuální výskyt textu.
          $offset = $pos + strlen($expression);
        }

        // Vložení zbytku textu za poslední výskyt zpět.
        if ($offset < strlen($text)) {
          $parent->insertBefore(
            $dom->createTextNode(substr($text, $offset)),
            $textNode
          );
        }

        // Původní textový uzel odstraníme (už je nahrazen novými uzly).
        $parent->removeChild($textNode);
      }
    }


    /**
     * Nastaví u daného elementu vlastnosti pro indicii.
     *
     * @param DOMElement $node         Element, který bude zvýrazněn jako indicie
     * @param int $idIndication        Identifikátor indicie
     * @return void
     */
    private static function applyIndicationAttributes($node, $idIndication) {
      if ($idIndication != null) {
        $node->setAttribute('href', '#indication-' . $idIndication . '-text');
        $node->setAttribute('id', 'indication-' . $idIndication);
        $node->setAttribute('class', 'indication anchor-link mark-indication');
        $node->setAttribute('data-indication', $idIndication);
      }
    }


    /**
     * Vrátí element obalující zvolený text označením indicie.
     *
     * @param DOMDocument $dom         Dokument, ve kterém dojde k vyznačení indicií
     * @param string $expression       Text, který se bude obalovat
     * @param int $idIndication        Identifikátor indicie
     * @return DOMElement              Element s textem obalený jako indicie
     * @throws DOMException
     */
    private static function createIndicationNode($dom, $expression, $idIndication) {
      $node = $dom->createElement('a');

      self::applyIndicationAttributes($node, $idIndication);

      $node->appendChild($dom->createTextNode($expression));
      $node->appendChild(self::createIconsNode($dom));

      return $node;
    }


    /**
     * Vrátí element obsahující ikonky k označení indicie.
     *
     * @param DOMDocument $dom         Dokument, ve kterém dochází k přidání elementu
     * @return DOMElement              Element s ikonkami
     * @throws DOMException
     */
    private static function createIconsNode($dom) {
      $node = $dom->createElement('span');
      $node->setAttribute('class', 'icons');

      $icons = ['alert-triangle', 'arrow-up-left'];

      foreach ($icons as $iconName) {
        $icon = $dom->createElement('span');

        $icon->setAttribute('class', 'icon');
        $icon->setAttribute('data-feather', $iconName);

        $node->appendChild($icon);
      }

      return $node;
    }


    /**
     * Vrátí element obalující zvolený text označením proměnné.
     *
     * @param DOMDocument $dom         Dokument, ve kterém dojde k vyznačení proměnné
     * @param string $expression       Text, který se bude obalovat
     * @return DOMElement              Element s textem obalený jako proměnná
     * @throws DOMException
     */
    private static function createVariableNode($dom, $expression) {
      $node = $dom->createElement('span');

      $node->setAttribute('class', 'email-variable');

      $node->appendChild($dom->createTextNode($expression));

      return $node;
    }


    /**
     * Vrátí element obalující zvolenou URL adresu označením indicie s tooltip komponentou.
     *
     * @param DOMDocument $dom          Dokument, ve kterém dojde k vyznačení
     * @param string $expression        URL adresa, která se bude obalovat
     * @param bool $withUrlText         TRUE (výchozí), pokud se má text vložit do elementu, jinak FALSE
     * @param bool $highlightAsVariable TRUE, pokud se má URL adresa navíc zvýraznit jako proměnná, jinak FALSE (výchozí)
     * @return DOMElement               Element s textem obalený jako proměnná
     * @throws DOMException
     */
    private static function createUrlNode($dom, $expression, $withUrlText = true, $highlightAsVariable = false) {
      $node = $dom->createElement('span');

      $escapedUrl = htmlspecialchars($expression, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
      $isFraudulentUrl = $expression == VAR_URL;

      $urlDescription = ($isFraudulentUrl) ? 'podvodnou' : '';

      $node->setAttribute('class', (($highlightAsVariable && $isFraudulentUrl) ? 'email-variable ' : '') . 'indication-link');
      $node->setAttribute('data-toggle', 'tooltip');
      $node->setAttribute('data-placement', 'right');
      $node->setAttribute('data-html', 'true');
      $node->setAttribute('data-original-title', 'Odkaz na ' . $urlDescription . ' stránku:<br><span class="text-monospace">' . $escapedUrl . '</span>');

      if ($withUrlText) {
        // Vloží viditelný text do elementu. Používá se např. u plain textu, kde není žádný <a> tag.
        $node->appendChild($dom->createTextNode($expression));
      }

      return $node;
    }


    /**
     * Vloží do hlaviček e-mailu označení indicií.
     *
     * @param array $email             Pole obsahující data o e-mailu
     * @param array $indications       Pole obsahující všechny indicie
     * @return array                   Pole obsahující data o e-mailu s vyznačenými indiciemi v hlavičkách e-mailu
     * @throws DOMException
     */
    public static function applyHeaderIndications($email, $indications) {
      $headerVariables = PhishingEmailModel::getEmailHeaderVariables();

      foreach ($indications as $indication) {
        if (!in_array($indication['expression'], $headerVariables)) {
          continue;
        }

        $headerKey = trim($indication['expression'], '%');

        if (!isset($email[$headerKey])) {
          continue;
        }

        $dom = new DOMDocument('1.0', 'UTF-8');

        // Nahrazení hodnoty dané hlavičky za hodnotu obalenou indicií.
        $node = self::createIndicationNode($dom, $email[$headerKey], $indication['id_indication']);
        $dom->appendChild($node);

        $email[$headerKey] = $dom->saveHTML($node);

        $email['_dom_processed_headers'][$headerKey] = true;
      }

      return $email;
    }


    /**
     * Vloží do těla e-mailu označení textových indicií.
     *
     * @param DOMDocument $dom         Dokument, ve kterém dojde k vyznačení indicií
     * @param array $indications       Pole obsahující všechny indicie, které mají být v těle e-mailu označeny
     * @return void
     * @throws DOMException
     */
    private static function applyTextIndications($dom, $indications) {
      foreach ($indications as $indication) {
        if (empty($indication['expression'])) {
          continue;
        }

        self::wrapExpression(
          $dom,
          $indication['expression'],
          fn($dom, $expr) => self::createIndicationNode($dom, $expr, $indication['id_indication'])
        );
      }
    }


    /**
     * Vloží do těla e-mailu označení proměnných.
     *
     * @param DOMDocument $dom         Dokument, ve kterém dojde k vyznačení proměnných
     * @return void
     * @throws DOMException
     */
    private static function applyVariableHighlighting($dom) {
      $xpath = new DOMXPath($dom);
      $textNodes = $xpath->query('//text()');

      foreach ($textNodes as $node) {
        if (!preg_match_all('/' . VAR_REGEXP . '/i', $node->nodeValue, $matches)) {
          continue;
        }

        foreach ($matches[0] as $variable) {
          if (strtolower($variable) === VAR_URL) {
            continue;
          }

          self::wrapExpression(
            $dom,
            $variable,
            fn($dom, $expr) => self::createVariableNode($dom, $expr)
          );
        }
      }
    }


    /**
     * Obalí všechny odkazy označením indicií, komponentou Tooltip a případně je zvýrazní jako proměnné.
     *
     * @param DOMDocument $dom          Dokument, ve kterém se budou odkazy obalovat
     * @param array $indications        Pole obsahující všechny indicie
     * @param bool $highlightAsVariable TRUE, pokud mají být URL zvýrazněny jako proměnné, jinak FALSE (výchozí)
     * @return void
     * @throws DOMException
     */
    private static function applyLinksHighlighting($dom, $indications, $highlightAsVariable = false) {
      $xpath = new DOMXPath($dom);
      $textNodes = iterator_to_array($xpath->query('//text()'));

      $fraudulentUrlIndication = EmailIndicationsModel::findIndicationIdByExpression($indications, VAR_URL);

      // Zpracování všech proměnných pro odkaz na podvodnou stránku.
      foreach ($textNodes as $textNode) {
        if (!str_contains($textNode->nodeValue, VAR_URL)) {
          continue;
        }

        if (self::isNodeInRestrictedContext($textNode->parentNode)) {
          continue;
        }

        self::wrapExpression(
          $dom,
          VAR_URL,
          fn($dom, $expr) => self::createUrlNode($dom, $expr, true, $highlightAsVariable)
        );
      }

      // Zpracování všech HTML odkazů.
      foreach ($xpath->query('//a[@href]') as $linkNode) {
        $href = $linkNode->getAttribute('href');

        if (!self::isSafeHref($href)) {
          continue;
        }

        $parent = $linkNode->parentNode;

        // Ověření, zdali už nebyl odkaz jednou obalen.
        if ($parent instanceof DOMElement && $parent->hasAttribute('class') && str_contains($parent->getAttribute('class'), 'indication-link')) {
          continue;
        }

        // Přidání označení indicie k HTML odkazu na podvodnou stránku.
        if ($href === VAR_URL) {
          self::applyFraudulentLinkIndication($dom, $linkNode, $fraudulentUrlIndication, $highlightAsVariable);
          continue;
        }

        // Vytvoření obalujícího elementu.
        $wrapper = self::createUrlNode($dom, $href, false, $highlightAsVariable);

        // Nahrazení původního odkazu obalujícím elementem.
        $parent->replaceChild($wrapper, $linkNode);
        $wrapper->appendChild($linkNode);
      }
    }


    /**
     * Vrátí element s HTML odkazem na podvodnou stránku včetně označení indicie a případně i proměnné.
     *
     * @param DOMDocument $dom          Dokument, ve kterém dojde k vyznačení odkazu
     * @param DOMElement $linkNode      Element obsahující HTML odkaz na podvodnou stránku
     * @param int $idIndication         Identifikátor indicie pro odkaz na podvodnou stránku
     * @param bool $highlightAsVariable TRUE, pokud má být odkaz zvýrazněn jako proměnná, jinak FALSE (výchozí)
     * @return DOMElement               Obalený element obsahující podvodný odkaz
     * @throws DOMException
     */
    private static function applyFraudulentLinkIndication($dom, $linkNode, $idIndication, $highlightAsVariable = false) {
      $href = $linkNode->getAttribute('href');

      // Přidání označení indicie.
      self::applyIndicationAttributes($linkNode, $idIndication);

      // Přidání tooltipu.
      $tooltipNode = self::createUrlNode($dom, $href, false, $highlightAsVariable);

      while ($linkNode->firstChild) {
        $tooltipNode->appendChild($linkNode->firstChild);
      }

      $linkNode->appendChild($tooltipNode);

      // Pokud bude odkaz zvýrazněn jako indicie, přidat ikonky indicie.
      if ($idIndication) {
        $linkNode->appendChild(self::createIconsNode($dom));
      }

      return $linkNode;
    }
  }