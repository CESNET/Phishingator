# Phishingator

Systém pro rozesílání cvičných phishingových zpráv



## Co je Phishingator?

Phishingator je webová aplikace, jejímž cílem je provádět **praktické školení uživatelů** v oblasti **phishingu a sociálního inženýrství**, a to odesíláním cvičných phishingových e-mailů.

Administrátor si ve Phishingatoru jednoduše vytvoří **cvičný phishingový e-mail** a s ním svázanou **cvičnou phishingovou stránku** (např. napodobující přihlášení do skutečného systému organizace). Phishingator pak ve zvolený den a čas odešle administrátorem **vybraným příjemcům** cvičný phishing. Administrátor následně může **v reálném čase sledovat**, jak **uživatelé** na cvičný phishing a podvodnou stránku **reagují**. Phishingator informuje, zda adresáti podvodnou stránku navštívili, zda vyplnili a odeslali přihlašovací údaje a pokud ano, pak také zda jsou přihlašovací údaje platné či nikoliv.

Pokud uživatel do cvičné phishingové stránky předá své **přihlašovací údaje**, je mu obratem zobrazena vzdělávací stránka s původně odeslaným phishingem, a to včetně **vyznačených indicií**, na základě kterých bylo možné podvod rozpoznat. Uživatel se tak má šanci ihned **poučit** a zjistit, jak mohl daný phishing rozpoznat tak, aby podobnému nebo dokonce skutečnému phishingu příště odolal. Stejné indicie jsou zobrazeny i všem ostatním uživatelům po ukončení školení.

Phishingator byl navržen jako co nejvíce **intuitivní a automatizovaný systém** tak, aby jeho používání nevyžadovalo téměř **žádné technické znalosti**. Součástí systému je vedení jak **globální**, tak **osobní statistiky** u každého z uživatelů, a také vedení **podrobné statistiky** u každé phishingové kampaně. Phishingator lze jednoduše napojit na již **existující SSO** (např. *OIDC*).



## Klíčové vlastnosti

- **Vytvoření cvičné phishingové kampaně** (školení)
  - Jednoduchý formulář s vyplněním *"komu, kdy, v kolik, jaký phishing a jaká phishingová stránka"*
  - Způsob vkládání příjemců
    - Dobrovolná registrace uživatelů přihlášením do Phishingatoru
    - Výběr administrátorem systému
      - Vypsáním seznamu uživatelů
      - Importem ze souboru
      - Interaktivním výběrem z LDAP
  - Předpřipravené šablony podvodných stránek
- **Průběh phishingové kampaně**
  - Rozeslání phishingových e-mailů, notifikací, vedení a ukončení kampaně automaticky zajišťuje Phishingator
  - Administrátor vidí reakce uživatelů
  - Vzdělávací stránka s vysvětlením a zobrazením indicií, na základě kterých bylo možné phishing rozpoznat
    - Obratem po vyplnění údajů na podvodné stránce (uživatel se má šanci ihned poučit)
- **Statistiky**
  - Podrobné statistiky u každé phishingové kampaně
  - Osobní statistiky uživatelů
  - Globální statistiky za celou organizaci
- **Modulární systém**
  - Jednoduché přidání nového podvodného e-mailu a podvodné stránky
  - Ověření platnosti jména a hesla zadaného na cvičné podvodné stránce
    - Podle různých autentizačních systémů – LDAP, webová služba, Kerberos, IMAP
    - Podle heslové politiky
- **Intuitivní**, téměř automatizovaný systém **vyžadující minimální obsluhu**
  - Optimalizováno pro mobilní zařízení
  - Živý vývoj



## Způsob nasazení

Phishingator **Vám můžeme nasadit** a pomoct s jeho **ovládáním a prvotním nastavením**, nebo si můžete Phishingator **nasadit sami** díky veřejně dostupným zdrojovým kódům. Pokud si Phishingator necháte nasadit od nás, budou v systému **předpřipravené** i cvičné podvodné e-maily a podvodné šablony podvodných stránek (včetně **zakoupených domén**).

**Možnosti konzultací**, **správy systému** ze strany sdružení CESNET a **školení** pak ukazuje následující tabulka:


|                                                | Samostatný provoz | Služba Phishingator |
|------------------------------------------------|:-----------------:|:-------------------:|
| Dostupnost zdrojových kódů                     |     &#10003;      |      &#10003;       |
| Instanci provozuje sdružení CESNET             |                   |      &#10003;       |
| Konzultace technických problémů                |     &#10003;      |      &#10003;       |
| Konzultace s napojením na autentizační systém  |                   |      &#10003;       |
| Vytvoření nových podvodných e-mailů (3&times;) |                   |      &#10003;       |
| Vytvoření nových podvodných stránek (3&times;) |                   |      &#10003;       |
| Příprava vzorové phishingové kampaně           |                   |      &#10003;       |
| Úvodní školení administrátorů systému          |                   |      &#10003;       |



## Mám zájem o službu

Pokud **máte zájem o zprovoznění** Phishingatoru ve Vaší organizaci, **napište nám**, prosím, na e-mail *phishingator@cesnet.cz*. Phishingator Vám nejprve na společné online schůzce předvedeme, a to včetně kompletního procesu tvorby ukázkové cvičné phishingové kampaně. Následně si ujasníme **technické detaily** a **způsob nasazení** Phishingatoru ve Vaší organizaci.

Prostředí Phishingatoru si můžete rovněž prohlédnout na několika ukázkových [screenshotech](SCREENSHOTS.md) s konkrétním popiskem.



## Odkazy

- [Phishingator Portál](https://phishingator.cesnet.cz)
- [Zdrojový kód Phishingatoru](/src)
- [Uživatelská příručka](MANUAL.md)
- [Ukázkové screenshoty](SCREENSHOTS.md)
- [Licence](LICENSE.md)



## O aplikaci

Phishingator původně vznikl na [Západočeské univerzitě v Plzni](https://www.zcu.cz) (ZČU) v roce 2019, a to jako výsledek bakalářské práce [Systém pro rozesílání cvičných phishingových zpráv](https://theses.cz/id/0kk18p/), jejímž autorem je Martin Šebela a vedoucím pak Aleš Padrta. Phishingator byl následně dále rozvíjen v [Centru informatizace a výpočetní techniky](https://civ.zcu.cz) na ZČU.


### Kontakt na vývojáře

- phishingator@cesnet.cz
- martin.sebela@cesnet.cz