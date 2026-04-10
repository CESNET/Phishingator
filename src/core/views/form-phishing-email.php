<hr>

<?php if (strip_tags($_inputsValues['body']) != $_inputsValues['body'] && !$inputsValues['html']): ?>
<div class="alert alert-with-icon alert-danger" role="alert">
  <div class="alert-icon pr-1">
    <span data-feather="alert-triangle"></span>
  </div>
  <div>
    <h4 class="alert-heading">Má jít o&nbsp;HTML e-mail?</h4>
    V&nbsp;těle e-mailu jsou použity HTML tagy, samotný e-mail ale není uložen jako HTML e-mail. Pokud má být e-mail odeslán v HTML formátu, stačí v dolní části formuláře zaškrtnout volbu <strong>Poslat jako HTML e-mail</strong>.
  </div>
</div>
<?php endif; ?>

<script src="/<?= CORE_DIR_EXTENSIONS ?>/tinymce/tinymce.min.js" referrerpolicy="origin" nonce="<?= HTTP_HEADER_CSP_NONCE ?>"></script>
<script nonce="<?= HTTP_HEADER_CSP_NONCE ?>">
  const EMAIL_VARIABLES = <?= $_phishingEmailVariables ?>;

  const VARIABLE_PATTERN = '<?= VAR_REGEXP ?>';
  const VARIABLE_REGEX = {
    exact: new RegExp(`^${VARIABLE_PATTERN}$`),
    atEnd: new RegExp(`${VARIABLE_PATTERN}$`)
  };

  const editorTextarea = '<?= $formPrefix ?>body';

  function isVariableExact(text) {
    return VARIABLE_REGEX.exact.test(text) && EMAIL_VARIABLES.includes(text);
  }

  function initEditor() {
    tinymce.init({
      selector: '#' + editorTextarea,
      menubar: false,
      plugins: 'link lists table code hr',
      toolbar: 'undo redo | bold italic underline | forecolor backcolor | bullist numlist | link table | hr blockquote | alignleft aligncenter alignright | removeformat | code',
      branding: false,
      license_key: 'gpl',
      entity_encoding: 'raw',
      encoding: 'UTF-8',
      valid_elements: `a[href|target|rel|class|data-indication|style],b[style],strong[style],i[style],em[style],u[style],s[style],br,p[style],ul[style],ol[style],li[style],table[width|cellpadding|cellspacing|border|style],tbody,thead,tr,td[colspan|rowspan|width|align|style],th[colspan|rowspan|width|align|style],span[class|data-url|contenteditable|style],hr,blockquote`,
      valid_styles: {'*': 'color,background-color,text-align,font-weight'},
      content_css: '/style-email.css',
      language: 'cs',

      setup: function(editor) {
        function wrapVariables() {
          const bodyNode = editor.getBody();

          if (!bodyNode || !bodyNode.textContent.includes('%')) {
            return;
          }

          const walker = document.createTreeWalker(bodyNode, NodeFilter.SHOW_TEXT);
          const nodes = [];

          while (walker.nextNode()) {
            nodes.push(walker.currentNode);
          }

          nodes.forEach(node => {
            const parent = node.parentNode;

            if (!parent || !node.nodeValue || !node.nodeValue.includes('%') ||
                parent.classList?.contains('email-variable') || parent.closest('a') || parent.closest('[contenteditable="false"]')) {
              return;
            }

            const regex = new RegExp(VARIABLE_PATTERN, 'gi');

            if (!node.nodeValue.match(regex)) {
              return;
            }

            regex.lastIndex = 0;

            const fragment = document.createDocumentFragment();
            let lastIndex = 0;

            node.nodeValue.replace(regex, (match, offset) => {
              fragment.appendChild(document.createTextNode(node.nodeValue.substring(lastIndex, offset)));

              if (EMAIL_VARIABLES.includes(match)) {
                fragment.appendChild(createVariableNode(match));
              }
              else {
                fragment.appendChild(document.createTextNode(match));
              }

              lastIndex = offset + match.length;
            });

            fragment.appendChild(document.createTextNode(node.nodeValue.substring(lastIndex)));

            node.parentNode.replaceChild(fragment, node);
          });
        }

        editor.on('SetContent', wrapVariables);
        editor.on('PastePostProcess', wrapVariables);

        editor.on('input', function() {
          const selectionRange = editor.selection.getRng();
          const containerNode = selectionRange.startContainer;

          if (containerNode.nodeType !== Node.TEXT_NODE) {
            return;
          }

          const parent = containerNode.parentNode;

          if (!parent || parent.closest('a') || parent.closest('[contenteditable="false"]')) {
            return;
          }

          const containerText = containerNode.nodeValue;
          const cursorPos = selectionRange.startOffset;
          const textBeforeCursor = containerText.slice(0, cursorPos);

          const match = textBeforeCursor.match(VARIABLE_REGEX.atEnd);

          if (!match) {
            return;
          }

          const variable = match[0];

          if (!EMAIL_VARIABLES.includes(variable)) {
            return;
          }

          const beforeVariable = containerText.slice(0, cursorPos - variable.length);
          const afterVariable = containerText.slice(cursorPos);

          const beforeVariableNode = document.createTextNode(beforeVariable);
          const afterVariableNode = document.createTextNode(afterVariable);
          const variableNode = createVariableNode(variable);

          parent.insertBefore(beforeVariableNode, containerNode);
          parent.insertBefore(variableNode, containerNode);
          parent.insertBefore(afterVariableNode, containerNode);
          parent.removeChild(containerNode);

          editor.selection.setCursorLocation(afterVariableNode, 0);
        });

        editor.on('GetContent', function(e) {
          const parser = new DOMParser();
          const parsedDoc = parser.parseFromString(e.content, 'text/html');

          parsedDoc.querySelectorAll('.email-variable').forEach(variableNode => {
            variableNode.replaceWith(variableNode.textContent);
          });

          e.content = parsedDoc.body.innerHTML;
        });

        editor.ui.registry.addAutocompleter('emailvariables', {
          trigger: '%',
          minChars: 0,
          fetch: (pattern) => {
            return Promise.resolve(
              EMAIL_VARIABLES.filter(variable => variable.toLowerCase().includes('%' + pattern.toLowerCase())).map(v => ({ value: v, text: v }))
            );
          },
          onAction: (api, rng, value) => {
            editor.selection.setRng(rng);
            editor.selection.setContent(createVariableNode(value).outerHTML + ' ');

            api.hide();
          }
        });
      }
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    const checkbox = document.getElementById('<?= $formPrefix ?>html');

    if (checkbox.checked && !tinymce.get(editorTextarea)) {
      initEditor();
    }

    checkbox.addEventListener('change', (e) => {
      const editor = tinymce.get(editorTextarea);

      if (e.target.checked && !editor) {
        initEditor();
      }
      else if (!e.target.checked && editor) {
        editor.destroy();
      }
    });
  });

  function createVariableNode(variable) {
    const node = document.createElement('span');

    node.className = 'email-variable';
    node.setAttribute('contenteditable', 'false');
    node.textContent = variable;

    return node;
  }
</script>

<form method="post" action="/portal/<?= $urlSection . '/' . $action . (($action == ACT_EDIT) ? '/' . $phishingEmail['id_email'] : ''); ?>">
  <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">

  <div class="form-row">
    <?php $input = 'name'; ?>
    <div class="form-group col-md-9 col-xl-11">
      <label for="<?= $formPrefix . $input ?>">Název</label>
      <input type="text" name="<?= $formPrefix . $input ?>" id="<?= $formPrefix . $input ?>" class="form-control" maxlength="<?= $inputsMaxLengths[$input] ?>" value="<?= $inputsValues[$input] ?>" required>
      <small class="form-text text-muted">Název slouží pouze pro vlastní pojmenování e-mailu.</small>
    </div>

    <?php $input = 'hidden'; ?>
    <div class="form-group col-md-7 col-xl-5 pl-md-5">
      <label class="d-none d-md-block">&nbsp;</label>
      <div class="custom-control custom-checkbox">
        <input type="checkbox" name="<?= $formPrefix . $input ?>" id="<?= $formPrefix . $input ?>" class="custom-control-input"<?= (($inputsValues[$input]) ? ' checked' : ''); ?>>
        <label for="<?= $formPrefix . $input ?>" class="custom-control-label">Skrýt před správci testů</label>
        <small class="form-text text-muted">E-mail uvidí a&nbsp;mohou rozesílat pouze administrátoři.</small>
      </div>
    </div>
  </div>

  <div class="form-row">
    <?php $input = 'sender-name'; ?>
    <div class="form-group col-md-8">
      <label for="<?= $formPrefix . $input ?>">Jméno odesílatele (nepovinné)</label>
      <input type="text" name="<?= $formPrefix . $input ?>" id="<?= $formPrefix . $input ?>" class="form-control" maxlength="<?= $inputsMaxLengths[$input] ?>" value="<?= $inputsValues[$input] ?>">
      <small class="form-text text-muted">Při nevyplnění bude použit e-mail odesílatele z&nbsp;následujícího pole, v&nbsp;opačném případě bude odesílatel uveden ve tvaru <code>Jméno &lt;email@domain.tld&gt;</code>.</small>
    </div>

    <?php $input = 'sender-email'; ?>
    <div class="form-group col-md-8">
      <label for="<?= $formPrefix . $input ?>">E-mail odesílatele</label>
      <input type="text" name="<?= $formPrefix . $input ?>" id="<?= $formPrefix . $input ?>" class="form-control" maxlength="<?= $inputsMaxLengths[$input] ?>" value="<?= $inputsValues[$input] ?>" required>
      <small class="form-text text-muted">Při použití proměnné <code class="replace-variable cursor-pointer" data-input="#<?= $formPrefix . $input ?>" data-var="<?= VAR_RECIPIENT_EMAIL ?>"><?= VAR_RECIPIENT_EMAIL ?></code> bude jako odesílatel uveden e-mail příjemce.</small>
    </div>
  </div>

  <?php $input = 'subject'; ?>
  <div class="form-group">
    <label for="<?= $formPrefix . $input ?>">Předmět</label>
    <input type="text" name="<?= $formPrefix . $input ?>" id="<?= $formPrefix . $input ?>" class="form-control" maxlength="<?= $inputsMaxLengths[$input] ?>" value="<?= $inputsValues[$input] ?>" required>
  </div>

  <div class="form-row">
    <?php $input = 'body'; ?>
    <div class="form-group col-lg-9 col-xl-11">
      <label for="<?= $formPrefix . $input ?>">Tělo</label>
      <textarea name="<?= $formPrefix . $input ?>" id="<?= $formPrefix . $input ?>" class="form-control text-monospace" rows="22" maxlength="<?= $inputsMaxLengths[$input] ?>"><?= $inputsValues[$input] ?></textarea>
      <small class="form-text text-muted">V&nbsp;těle e-mailu lze používat proměnné, které budou při odeslání e-mailu nahrazeny zvoleným obsahem.</small>
    </div>
    <div class="form-group col-lg-7 col-xl-5">
      <label>Proměnné</label>
      <p class="text-muted">V&nbsp;těle e-mailu lze používat následující proměnné (kliknutím na proměnnou dojde ke vložení):</p>
      <label>Povinné proměnné</label>
      <ul class="form-text text-muted list-unstyled <?= $formPrefix ?>variables">
        <li><code class="cursor-pointer" data-var="<?= VAR_URL ?>"><?= VAR_URL ?></code> &ndash; URL podvodné stránky svázané s&nbsp;e-mailem</li>
      </ul>
      <label>Proměnné vztahující se k&nbsp;příjemci</label>
      <ul class="form-text text-muted list-unstyled <?= $formPrefix ?>variables">
        <li><code class="cursor-pointer" data-var="<?= VAR_RECIPIENT_USERNAME ?>"><?= VAR_RECIPIENT_USERNAME ?></code> &ndash; uživatelské jméno příjemce</li>
        <li><code class="cursor-pointer" data-var="<?= VAR_RECIPIENT_EMAIL ?>"><?= VAR_RECIPIENT_EMAIL ?></code> &ndash; e-mail příjemce</li>
        <li><code class="cursor-pointer" data-var="<?= VAR_RECIPIENT_FULLNAME ?>"><?= VAR_RECIPIENT_FULLNAME ?></code> &ndash; jméno a&nbsp;příjmení příjemce</li>
        <li><code class="cursor-pointer" data-var="<?= VAR_RECIPIENT_FIRSTNAME ?>"><?= VAR_RECIPIENT_FIRSTNAME ?></code> &ndash; jméno příjemce</li>
        <li><code class="cursor-pointer" data-var="<?= VAR_RECIPIENT_SURNAME ?>"><?= VAR_RECIPIENT_SURNAME ?></code> &ndash; příjmení příjemce</li>
      </ul>
      <label>Další proměnné</label>
      <ul class="form-text text-muted list-unstyled <?= $formPrefix ?>variables">
        <li><code class="cursor-pointer" data-var="<?= VAR_DATE_CZ ?>"><?= VAR_DATE_CZ ?></code> &ndash; datum&nbsp;odeslání e-mailu v&nbsp;českém formátu (<?= date(VAR_DATE_CZ_FORMAT) ?>)</li>
        <li><code class="cursor-pointer" data-var="<?= VAR_DATE_EN ?>"><?= VAR_DATE_EN ?></code> &ndash; datum&nbsp;odeslání e-mailu ve&nbsp;formátu <samp>YYYY-MM-DD</samp> (<?= date(VAR_DATE_EN_FORMAT) ?>)</li>
      </ul>
      <label>HTML tagy</label>
      <p class="text-muted">
        Pro povolení HTML tagů je nutné zaškrtnout volbu <span class="badge badge-secondary">Poslat jako HTML e-mail</span>.<br>
        Příklad HTML tagu pro odkaz v&nbsp;e-mailu:
      </p>
      <ul class="form-text text-muted list-unstyled <?= $formPrefix ?>variables">
        <li><code class="cursor-pointer" data-var='<a href="<?= VAR_URL ?>">odkaz</a>' data-type="html">&lt;a href="<?= VAR_URL ?>"&gt;odkaz&lt;/a&gt;</code> &ndash; odkaz na podvodnou stránku</li>
        <li><code class="cursor-pointer" data-var='<a href="https://">odkaz</a>' data-type="html">&lt;a href="https://&hellip;"&gt;odkaz&lt;/a&gt;</code> &ndash; libovolný HTTP(S) odkaz</li>
      </ul>
    </div>
  </div>

  <?php $input = 'html'; ?>
  <div class="form-group">
    <div class="custom-control custom-checkbox">
      <input type="checkbox" name="<?= $formPrefix . $input ?>" id="<?= $formPrefix . $input ?>" class="custom-control-input"<?= (($action == ACT_NEW && empty($inputsValues[$input]) || $inputsValues[$input]) ? ' checked' : ''); ?>>
      <label for="<?= $formPrefix . $input ?>" class="custom-control-label">Poslat jako HTML e-mail</label>
      <small class="form-text text-muted">Povolí používání vybraných HTML tagů z&nbsp;aktivovaného editoru a&nbsp;samotný e-mail bude při použití v&nbsp;kampani odeslán v&nbsp;HTML formátu.</small>
    </div>
  </div>

  <div class="d-flex justify-content-center">
    <button type="submit" name="<?= $formPrefix . $action ?>" class="btn btn-primary btn-lg ml-1 order-2">
      <span data-feather="save"></span>
      <?= ($action == ACT_NEW) ? 'Přidat' : 'Uložit změny'; ?>
    </button>

    <button type="submit" name="<?= $formPrefix . ACT_PREVIEW ?>" formtarget="_blank" class="btn btn-secondary btn-lg">
      <span data-feather="eye"></span>
      Náhled
    </button>
  </div>
</form>