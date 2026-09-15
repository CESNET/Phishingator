<hr>

<?php if ($action == ACT_EDIT && $campaign['status'] == 'running'): ?>
<div class="alert alert-with-icon alert-warning" role="alert">
  <div class="alert-icon pr-1">
    <span data-feather="activity"></span>
  </div>
  <div>
    <h4 class="alert-heading">Kampaň právě probíhá</h4>
    <p>Jakákoliv klíčová úprava parametrů kampaně po jejím spuštění (tedy po odeslání prvních e-mailů) může způsobit nevratné akce a&nbsp;zkreslení ve výsledné statistice kampaně!</p>
    <form method="post" action="/portal/<?= $urlSection . '/' . ACT_STOP . '/' . $campaign['id_campaign'] ?>">
      <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">

      <a href="/portal/<?= $urlSection . '/' . ACT_STATS . '/' . $campaign['id_campaign'] ?>" class="btn btn-info mr-1">
        <span data-feather="bar-chart"></span>
        Zobrazit statistiku
      </a>

      <button type="submit" name="<?= $formPrefix . $action ?>" class="btn btn-danger btn-confirm" data-confirm="Opravdu chcete kampaň předčasně ukončit? Dojde tak k okamžitému znepřístupnění podvodné stránky (uživatelé budou při vstupu na podvodnou stránku automaticky přesměrováni na vzdělávací stránku) a případně (podle nastavení kampaně) dojde k rozeslání notifikací o absolvování cvičného phishingu.">
        <span data-feather="x-octagon"></span>
        Předčasně ukončit
      </button>
    </form>
  </div>
</div>

<hr>
<?php elseif ($action == ACT_EDIT && $campaign['status'] == 'ended'): ?>
<div class="alert alert-with-icon alert-info" role="alert">
  <div class="alert-icon pr-1">
    <span data-feather="flag"></span>
  </div>
  <div>
    <h4 class="alert-heading">Kampaň již proběhla</h4>
    <p>Jakákoliv klíčová úprava parametrů kampaně po jejím ukončení (kromě změny názvu a&nbsp;čísla lístku) může způsobit nevratné akce a&nbsp;zkreslení ve výsledné statistice kampaně!</p>
    <a href="/portal/<?= $urlSection . '/' . ACT_STATS . '/' . $campaign['id_campaign'] ?>" class="btn btn-info">
      <span data-feather="bar-chart"></span>
      Přejít na výsledky
    </a>
  </div>
</div>

<hr>
<?php endif; ?>

<form method="post" action="/portal/<?= $urlSection . '/' . $action . (($action == ACT_EDIT) ? '/' . $campaign['id_campaign'] : ''); ?>">
  <div class="row">
    <div class="col-lg-10 col-xl-11">
      <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">

      <div class="row">
        <?php $input = 'name'; ?>
        <div class="form-group col-xl-10">
          <label for="<?= $formPrefix . $input ?>">Název</label>
          <input type="text" class="form-control" id="<?= $formPrefix . $input ?>" name="<?= $formPrefix . $input ?>" maxlength="<?= $inputsMaxLengths[$input] ?>" value="<?= $inputsValues[$input] ?>" required>
          <small class="form-text text-muted">Název slouží pouze pro vlastní pojmenování kampaně.</small>
        </div>

        <?php $input = 'id-ticket'; ?>
        <div class="form-group col-xl-6">
          <label for="<?= $formPrefix . $input ?>">Číslo lístku s&nbsp;kampaní (nepovinné)</label>
          <input type="number" class="form-control" id="<?= $formPrefix . $input ?>" name="<?= $formPrefix . $input ?>" maxlength="<?= $inputsMaxLengths[$input] ?>" value="<?= $inputsValues[$input] ?>" min="1">
          <small class="form-text text-muted">Číslo lístku (ticketu) v&nbsp;RT systému ohledně vytvoření phishingové kampaně.</small>
        </div>
      </div>

      <div class="row">
        <?php $input = 'id-email'; ?>
        <div class="form-group col-sm-12 col-md-12 col-lg-12 col-xl-13">
          <label for="<?= $formPrefix . $input ?>">Rozesílaný podvodný e-mail</label>
          <select name="<?= $formPrefix . $input ?>" id="<?= $formPrefix . $input ?>" class="custom-select set-preview-btn" data-preview-btn="#btn-email-preview" data-preview-link="/portal/phishing-emails" required>
            <option value="0">Vyberte&hellip;</option>
            <?php foreach ($emails as $email): ?>
            <option value="<?= $email['id_email']; ?>"<?= (($inputsValues[$input] == $email['id_email']) ? ' selected': ''); ?>><?= $email['type'] . ' &ndash; ' . $email['name'] ?></option>
            <?php endforeach; ?>
          </select>
          <small class="form-text text-muted">Podvodný e-mail, který bude doručen zvoleným příjemcům a&nbsp;ze kterého se budou moci dostat na podvodnou stránku.</small>
        </div>

        <div class="form-group col-sm-4 col-md-4 col-lg-4 col-xl-3 text-right">
          <label class="d-none d-sm-block">&nbsp;</label>
          <a href="/portal/phishing-emails<?= (($inputsValues[$input]) ? '/' . ACT_PREVIEW . '/' . $inputsValues[$input] : '') ?>" target="_blank" class="btn btn-outline-secondary" id="btn-email-preview">
            <span data-feather="eye"></span>
            Náhled
          </a>
        </div>
      </div>

      <div class="row">
        <?php $input = 'id-website'; ?>
        <div class="form-group col-sm-12 col-md-12 col-lg-12 col-xl-13">
          <label for="<?= $formPrefix . $input ?>">Podvodná webová stránka přístupná z&nbsp;e-mailu</label>
          <select name="<?= $formPrefix . $input ?>" id="<?= $formPrefix . $input ?>" class="custom-select set-preview-btn" data-preview-btn="#btn-website-preview" data-preview-link="/portal/phishing-websites" required>
            <option value="0">Vyberte&hellip;</option>
            <?php foreach ($websites as $website): ?>
            <option value="<?= $website['id_website'] ?>"<?= (($inputsValues[$input] == $website['id_website']) ? ' selected': ''); ?>><?= $website['name'] . ' &ndash; ' . $website['url'] ?></option>
            <?php endforeach; ?>
          </select>
          <small class="form-text text-muted">Podvodná stránka, na kterou se uživatel dostane přes odkaz v&nbsp;podvodném e-mailu.</small>
        </div>

        <div class="form-group col-sm-4 col-md-4 col-lg-4 col-xl-3 text-right">
          <label class="d-none d-sm-block">&nbsp;</label>
          <a href="/portal/phishing-websites<?= (($inputsValues[$input]) ? '/' . ACT_PREVIEW . '/' . $inputsValues[$input] : '') ?>" target="_blank" class="btn btn-outline-secondary" id="btn-website-preview">
            <span data-feather="eye"></span>
            Náhled
          </a>
        </div>
      </div>

      <?php $input = 'id-onsubmit'; ?>
      <div class="form-group">
        <label for="<?= $formPrefix . $input ?>">Akce po odeslání formuláře</label>
        <select class="custom-select" id="<?= $formPrefix . $input ?>" name="<?= $formPrefix . $input ?>" required>
          <option value="0">Vyberte&hellip;</option>
          <?php foreach ($websiteActions as $onsubmit): ?>
          <option value="<?= $onsubmit['id_onsubmit'] ?>"<?= (($inputsValues[$input] == $onsubmit['id_onsubmit'] || ($action == ACT_NEW && empty($inputsValues[$input]) && $onsubmit['id_onsubmit'] == CAMPAIGN_DEFAULT_ONSUBMIT_ACTION)) ? ' selected': ''); ?>><?= $onsubmit['name'] ?></option>
          <?php endforeach; ?>
        </select>
        <small class="form-text text-muted">Co se stane tehdy, pokud uživatel na podvodné stránce vyplní formulář a&nbsp;klikne na tlačítko pro jeho odeslání.</small>
      </div>

      <div class="form-row">
        <?php $input = 'date-active-since'; ?>
        <div class="form-group col-md-8">
          <label for="<?= $formPrefix . $input ?>">Datum zahájení kampaně</label>
          <input type="date" class="form-control" id="<?= $formPrefix . $input ?>" name="<?= $formPrefix . $input ?>" maxlength="<?= $inputsMaxLengths[$input] ?>" value="<?= $inputsValues[$input]; ?>" min="<?= (($action == ACT_NEW) ? date('Y-m-d') : $inputsValues[$input]) ?>" required>
          <small class="form-text text-muted">Den, kdy započne rozesílání e-mailů zvoleným příjemcům a&nbsp;zároveň den, od kterého bude přístupná podvodná stránka.</small>
        </div>

        <div class="form-group col-md-8">
          <?php $input = 'time-active-since'; ?>
          <label for="<?= $formPrefix . $input ?>">Čas zahájení</label>
          <input type="time" class="form-control" id="<?= $formPrefix . $input ?>" name="<?= $formPrefix . $input ?>" maxlength="<?= $inputsMaxLengths[$input] ?>" value="<?= $inputsValues[$input] ?>" required>
          <small class="form-text text-muted">Čas, kdy se zahájí rozesílání vybraného e-mailu zvoleným příjemcům a&nbsp;kdy začne být dostupná podvodná stránka.</small>
        </div>
      </div>

      <div class="form-row">
        <?php $input = 'date-active-to'; ?>
        <div class="form-group col-md-8">
          <label for="<?= $formPrefix . $input ?>">Datum ukončení kampaně</label>
          <input type="date" name="<?= $formPrefix . $input ?>" id="<?= $formPrefix . $input ?>" class="form-control" maxlength="<?= $inputsMaxLengths[$input] ?>" value="<?= $inputsValues[$input]; ?>" min="<?= (($action == ACT_NEW) ? date('Y-m-d') : $inputsValues['date-active-since']) ?>" required>
          <small class="form-text text-muted">Do jakého data bude kampaň aktivní, tzn. do jakého data budou sbírány výsledky a&nbsp;do jakého data bude přístupná zvolená podvodná stránka.</small>
        </div>

        <?php $input = 'time-active-to'; ?>
        <div class="form-group col-md-8">
          <label for="<?= $formPrefix . $input ?>">Čas ukončení</label>
          <input type="time" name="<?= $formPrefix . $input ?>" id="<?= $formPrefix . $input ?>" class="form-control" maxlength="<?= $inputsMaxLengths[$input] ?>" value="<?= ($action == ACT_NEW && empty($inputsValues[$input])) ? '23:59' : $inputsValues[$input] ?>" required>
          <small class="form-text text-muted">Čas, kdy přestane být podvodná stránka dostupná a&nbsp;uživatel bude při jejím navštívení obratem přesměrován na vzdělávací stránku.</small>
        </div>
      </div>

      <div class="form-group">
        <?php $input = 'send-users-notification'; ?>
        <div class="custom-control custom-checkbox">
          <input type="checkbox" name="<?= $formPrefix . $input ?>" id="<?= $formPrefix . $input ?>" class="custom-control-input campaign-send-users-notification"<?= (($action == ACT_NEW && empty($inputsValues[$input]) || $inputsValues[$input]) ? ' checked' : ''); ?>>
          <label for="<?= $formPrefix . $input ?>" class="custom-control-label">Odeslat příjemcům notifikaci po ukončení kampaně</label>
          <small class="form-text text-muted">Notifikace obsahuje informaci o&nbsp;absolvování cvičného phishingu a&nbsp;odkaz na vzdělávací stránku s&nbsp;indiciemi.</small>
          <div class="mt-2">
            <?php $input = 'users-notification-language'; ?>
            <label class="d-block mt-3">Jazyk notifikace</label>
            <?php foreach ($languages as $language => $languageDescription): ?>
            <div class="custom-control custom-checkbox d-inline-block mr-4">
              <input type="checkbox" name="<?= $formPrefix . $input ?>[]" id="<?= $formPrefix . $input . '-' . $language ?>" value="<?= $language ?>" class="custom-control-input"<?= ((($action == ACT_NEW && empty($inputsValues[$input]) && $language == 'cz') || (!empty($inputsValues[$input]) && in_array($language, $inputsValues[$input]))) ? ' checked' : '') . ((($action == ACT_NEW && empty($inputsValues[$input])) || (!empty($inputsValues['send-users-notification']))) ? '' : ' disabled'); ?>>
              <label for="<?= $formPrefix . $input . '-' . $language ?>" class="custom-control-label">
                <?= $languageDescription[1] . '&nbsp;' . $languageDescription[0] ?>
              </label>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-6 col-xl-5">
      <?php $input = 'recipients'; ?>
      <div class="form-group">
        <label for="<?= $formPrefix . $input ?>">Seznam příjemců</label>
        <span class="float-right">
          Celkem: <strong id="countRecipients"><?= $countRecipients ?></strong>
        </span>
        <textarea name="<?= $formPrefix . $input ?>" id="<?= $formPrefix . $input ?>" class="form-control text-monospace" rows="24" required><?= $recipients ?></textarea>
        <div class="form-text text-muted">
          <div>
            <small>Každý z&nbsp;příjemců musí být umístěn na samostatném řádku.</small>
          </div>

          <div class="d-flex justify-content-between align-items-top mt-2 mb-4">
            <div class="mr-3">
              <button type="button" class="btn btn-outline-danger btn-sm mb-1" data-toggle="modal" data-target="#removeRecipientsDialog">
                <span data-feather="user-x"></span>
                Hromadně odebrat
              </button>
            </div>

            <div class="text-right">
              <input type="file" id="file-recipients" class="d-none">

              <button type="button" class="btn btn-secondary btn-sm mb-1 import-recipients">
                <span data-feather="upload"></span>
                Importovat příjemce
              </button>

              <button type="button" class="btn btn-secondary btn-sm mb-1 select-recipients" data-toggle="modal" data-target="#recipientsDialog">
                <span data-feather="user-check"></span>
                Vybrat příjemce
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="recipientsDialog" tabindex="-1" role="dialog" aria-labelledby="recipientsDialogTitle" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="recipientsDialogTitle">Výběr příjemců na základě skupin</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="container-fluid">
              <div class="row">
                <?php if (PermissionsModel::getUserRole() == PERMISSION_ADMIN): ?>
                <div class="col-6">
                  <a href="/portal/user-groups/<?= ACT_EDIT ?>/1#groups" target="_blank" class="btn btn-secondary btn-sm">
                    <span data-feather="grid"></span>
                    Upravit zobrazované skupiny
                  </a>
                </div>
                <?php endif; ?>
                <div class="col text-right align-content-center">
                  <?php if ($recipientsVolunteers == null && count($recipientsLdapGroups) == 0): ?>
                  <p class="m-0<?php if (PermissionsModel::getUserRole() != PERMISSION_ADMIN): ?> text-left<?php endif; ?>">
                    Zatím nebyly administrátorem nastaveny skupiny k&nbsp;zobrazení.
                  </p>
                  <?php else: ?>
                  <button type="button" class="btn btn-outline-secondary btn-sm mark-all-checkboxes" data-checkboxes-group="#recipientsDialog">
                    <span data-feather="user-check"></span>
                    Vybrat všechny příjemce
                  </button>

                  <button type="button" class="btn btn-outline-secondary btn-sm expand-all-groups" data-toggle="button" aria-pressed="false">
                    <span data-feather="users"></span>
                    Rozbalit všechny skupiny
                  </button>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <?php if ($recipientsVolunteers != null): ?>
            <div class="container-fluid pt-3 mt-3 border-top">
              <div class="row">
                <div class="col-lg-11">
                  <h6>
                    <label>
                      <input type="checkbox" id="group-checkbox-volunteers" class="mark-group-checkboxes" data-checkboxes-group="#cover-volunteers" data-checkboxes-group-checked="0" data-checkboxes-group-total="<?= count($recipientsVolunteers) ?>">
                      Dobrovolně registrovaní příjemci
                    </label>
                  </h6>
                </div>
                <div class="col-lg-5 mb-2 mb-lg-0 text-right">
                  <button type="button" class="btn btn-outline-secondary btn-sm btn-toggle-display" data-toggle="#cover-volunteers">
                    <span data-feather="user-check"></span>
                    Seznam příjemců <span id="group-checkbox-volunteers-count" class="badge badge-secondary"><?= count($recipientsVolunteers) ?></span>
                  </button>
                </div>
              </div>

              <div id="cover-volunteers" class="group-recipients d-none">
                <small>Číslo u&nbsp;příjemce udává zbývající počet e-mailů, o&nbsp;které má zájem.</small>
                <div class="d-flex flex-row flex-wrap mt-2">
                  <?php foreach ($recipientsVolunteers as $volunteer): ?>
                  <label class="recipients-list-email text-truncate">
                    <input type="checkbox" value="<?= $volunteer['email'] ?>" class="mark-same-checkboxes" data-email-lowercase="<?= mb_strtolower($volunteer['email']) ?>" data-group-checkbox="#group-checkbox-volunteers"<?php if ($volunteer['checked']): ?> checked<?php endif; ?>>&nbsp;<?= ((!empty($volunteer['color'])) ? '<span class="badge badge-' . $volunteer['color'] . '">' . $volunteer['username'] . '</span>@' . $volunteer['domain'] : $volunteer['email']) . ((!empty($volunteer['email_limit'])) ? '&nbsp;<span class="badge badge-secondary">' . $volunteer['email_limit'] . '</span>' : '') ?>
                  </label>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
            <?php endif; ?>

            <?php foreach ($recipientsLdapGroups as $groupName => $groupUsers): ?>
            <div class="container-fluid pt-3 mt-3 border-top">
              <div class="row">
                <div class="col-lg-11">
                  <h6>
                    <label>
                      <input type="checkbox" id="group-checkbox-<?= remove_special_chars($groupName) ?>" class="mark-group-checkboxes" data-checkboxes-group="#group-<?= remove_special_chars($groupName) ?>" data-checkboxes-group-checked="0" data-checkboxes-group-total="<?= count($groupUsers) ?>">
                      <?= stripcslashes($groupName) ?><?php if (!empty($ldapGroupsDescription[$groupName])): ?><span class="font-weight-normal text-muted"> &ndash; <?=$ldapGroupsDescription[$groupName]; ?></span><?php endif; ?>
                    </label>
                  </h6>
                </div>
                <div class="col-lg-5 mb-2 mb-lg-0 text-right">
                  <button type="button" class="btn btn-outline-secondary btn-sm btn-toggle-display" data-toggle="#group-<?= remove_special_chars($groupName) ?>">
                    <span data-feather="user-check"></span>
                    Seznam příjemců <span id="group-checkbox-<?= remove_special_chars($groupName) ?>-count" class="badge badge-secondary"><?= count($groupUsers) ?></span>
                  </button>
                </div>
              </div>

              <div id="group-<?= remove_special_chars($groupName) ?>" class="group-recipients d-none">
                <div class="d-flex flex-row flex-wrap mt-2">
                  <?php foreach ($groupUsers as $user): ?>
                  <label class="text-truncate">
                    <input type="checkbox" value="<?= $user['email'] ?>" class="mark-same-checkboxes" data-email-lowercase="<?= mb_strtolower($user['email']) ?>" data-group-checkbox="#group-checkbox-<?= remove_special_chars($groupName) ?>"<?php if ($user['checked']): ?> checked<?php endif; ?>>&nbsp;<?= ((!empty($user['color'])) ? '<span class="badge badge-' . $user['color'] . '">' . $user['username'] . '</span>@' . $user['domain'] : $user['email']); ?>
                  </label>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">
              <span data-feather="x"></span>
              Zavřít
            </button>
            <button type="button" class="btn btn-primary insert-recipients-emails">
              <span data-feather="save"></span>
              Uložit změny
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="removeRecipientsDialog" tabindex="-1" role="dialog" aria-labelledby="removeRecipientsDialogTitle" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="removeRecipientsDialogTitle">Hromadné odebrání příjemců podle seznamu</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <?php $input = 'remove-recipients'; ?>
            <label for="<?= $formPrefix . $input ?>">E-maily k&nbsp;odebrání</label>
            <textarea id="<?= $formPrefix . $input ?>" class="form-control text-monospace" rows="20"></textarea>
            <small class="text-muted">Každý e-mail, který má být ze seznamu příjemců odstraněn, musí být umístěn na samostatném řádku.</small>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">
              <span data-feather="x"></span>
              Zavřít
            </button>
            <button type="button" class="btn btn-danger remove-recipients-emails">
              <span data-feather="user-x"></span>
              Odebrat ze seznamu
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="text-center">
    <button type="submit" name="<?= $formPrefix . $action ?>" class="btn btn-primary btn-lg btn-confirm"<?php if ($action == ACT_EDIT && ($campaign['status'] == 'running' || $campaign['status'] == 'ended')): ?> data-confirm="Opravdu chcete upravit kampaň i přesto, že může mít vliv na výslednou statistiku kampaně?"<?php endif; ?>>
      <span data-feather="save"></span>
      <?= ($action == ACT_NEW) ? 'Přidat' : 'Uložit změny'; ?>
    </button>
  </div>
</form>