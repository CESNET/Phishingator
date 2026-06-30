<hr>

<form method="post" action="/portal/<?= $urlSection . '/' . $action . (($action == ACT_EDIT) ? '/' . $user['id_user'] : ''); ?>">
  <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">

  <?php if ($action == ACT_EDIT && !empty($name)): ?>
  <div class="row mb-2">
    <div class="col-md">
      <?php $input = 'name'; ?>
      <div class="form-group">
        <label for="<?= $formPrefix . $input ?>">Jméno a&nbsp;příjmení</label>
        <input type="text" id="<?= $formPrefix . $input ?>" class="form-control-plaintext" value="<?= $name ?>" readonly>
      </div>
    </div>

    <div class="col-md">
      <?php $departments = explode(LDAP_GROUPS_DELIMITER, $user['departments']) ?>
      <div class="form-group">
        <label>Oddělení <span class="badge badge-dark badge-pill"><?= count($departments) ?></span></label>
        <div>
          <ul class="list-unstyled">
          <?php foreach ($departments as $department): ?>
            <li><?= $department ?></li>
          <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($action == ACT_EDIT && empty($name)): ?>
  <div class="alert alert-with-icon alert-warning" role="alert">
    <div class="alert-icon pr-1">
      <span data-feather="eye-off"></span>
    </div>
    <div>
      <h4 class="alert-heading">Pozor, uživatel již není ve Vaší organizaci!</h4>
      O&nbsp;uživateli se nepodařilo získat žádné informace z&nbsp;LDAP.
    </div>
  </div>
  <?php endif; ?>

  <div class="row mb-2">
    <div class="col-md">
      <?php $input = 'email'; ?>
      <div class="form-group">
        <label for="<?= $formPrefix . $input ?>">E-mail</label>
        <input type="email" name="<?= $formPrefix . $input ?>" id="<?= $formPrefix . $input ?>" class="form-control" maxlength="<?= $inputsMaxLengths[$input] ?>" value="<?= $inputsValues[$input] ?>" required>
        <small class="form-text text-muted">E-mail, přes který se uživatel do Phishingatoru přihlašuje a&nbsp;na který mu budou odesílány cvičné phishingové zprávy. E-mail musí pocházet z domény <?= $_allowedDomains ?>.</small>
      </div>
    </div>

    <div class="col-md">
      <?php $input = 'id-user-group'; ?>
      <div class="form-group">
        <label for="<?= $formPrefix . $input ?>">Skupina</label>
        <select name="<?= $formPrefix . $input ?>" id="<?= $formPrefix . $input ?>" class="custom-select" required>
          <option value="nothing">Vyberte&hellip;</option>
          <?php foreach ($groups as $group): ?>
          <option value="<?= $group['id_user_group'] ?>"<?= (($inputsValues[$input] == $group['id_user_group']) ? ' selected': ''); ?>><?= $group['name'] ?> (opr. <?= $group['role_name'] ?>)</option>
          <?php endforeach; ?>
        </select>
        <small class="form-text text-muted">Skupina, na základě které uživatel získá oprávnění ve Phishingatoru.</small>
      </div>
    </div>
  </div>

  <?php if ($action == ACT_EDIT): ?>
  <label id="<?= ACT_STATS ?>">Detaily o&nbsp;uživateli</label>

  <div class="table-responsive mb-2">
    <table class="table table-striped table-hover">
      <thead>
        <tr>
          <th>Registrován</th>
          <th>Poslední přihlášení</th>
          <th>Dobrovolník</th>
          <th>Úspěšnost</th>
          <th>Odesláno e-mailů</th>
          <th>Limit e-mailů</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>
            <?= $user['date_added'] ?>
            <span class="badge badge-<?= $user['voluntary_registration_color'] ?>">
              <?= $user['voluntary_registration'] ?>
            </span>
          </td>
          <td><?= $user['date_login'] ?></td>
          <td>
            <span class="badge badge-<?= $user['recieve_email_color'] ?>">
              <?= $user['recieve_email_text'] ?>
            </span>
            <small><?= $user['date_participation'] ?></small>
          </td>
          <td>
            <span class="badge badge-<?= $user['success_rate_color'] ?>">
              <?= $user['success_rate'] ?>&nbsp;%
            </span>
          </td>
          <td><?= $user['recieved_emails_count'] ?></td>
          <td><?= $user['email_limit'] ?></td>
        </tr>
      </tbody>
    </table>
  </div>

  <label>Absolvované phishingové kampaně</label>

  <script src="/assets/vendor/table-sort.min.js" nonce="<?= HTTP_HEADER_CSP_NONCE ?>"></script>

  <div class="table-responsive mb-5">
    <table class="table table-striped table-hover records-list table-sort table-arrows">
      <thead>
        <tr>
          <th colspan="2">Kampaň</th>
          <th colspan="2">Podvodný e-mail</th>
          <th colspan="2">Podvodná stránka</th>
          <th class="data-sort">Odesláno</th>
          <th colspan="2">Reakce uživatele</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($campaigns as $campaign): ?>
        <tr>
          <td><?= $campaign['name'] ?></td>
          <td>
            <a href="/portal/campaigns/<?= ACT_STATS . '/' . $campaign['id_campaign'] ?>" class="badge badge-info text-nowrap">
              <span data-feather="bar-chart-2"></span>
              Statistika
            </a>
          </td>
          <td><?= $campaign['subject'] ?></td>
          <td>
            <a href="/portal/phishing-emails/<?= ACT_PREVIEW . '/' . $campaign['id_email'] ?>" class="badge badge-secondary" role="button" title="Náhled">
              <span data-feather="eye"></span>
            </a>
          </td>
          <td class="nowrap maxw-20-rem text-truncate">
            <span class="badge badge-<?= $campaign['url_protocol_color'] ?>">
              <?= $campaign['url_protocol'] ?>
            </span><?= str_replace(VAR_RECIPIENT_URL, '<code>' . VAR_RECIPIENT_URL . '</code>', $campaign['url']) ?>
          </td>
          <td>
            <a href="/portal/phishing-websites/<?= ACT_PREVIEW . '/' . $campaign['id_website'] ?>" target="_blank" class="badge badge-secondary" role="button" title="Náhled">
              <span data-feather="eye"></span>
            </a>
          </td>
          <td data-sort="<?= $campaign['date_sent'] ?>"><?= $campaign['date_sent_formatted'] ?></td>
          <td>
            <span class="badge badge-<?= $campaign['user_response']['css_color_class'] ?>">
              <?= $campaign['user_response']['name'] ?>
            </span>
          </td>
          <td>
            <?php if ($campaign['user_response']['id_action'] != CAMPAIGN_NO_REACTION_ID): ?>
            <a href="/portal/campaigns/<?= ACT_STATS . '/' . $campaign['id_campaign'] . '?' . ACT_STATS_WEBSITE_ACTIONS . '&amp;rows=' . $user['id_user'] . '#d' . $campaign['user_response']['id_captured_data'] ?>" class="badge badge-secondary" role="button" title="Náhled">
              <span data-feather="eye"></span>
            </a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <div class="text-center">
    <button type="submit" name="<?= $formPrefix . $action ?>" class="btn btn-primary btn-lg">
      <span data-feather="save"></span>
      <?= ($action == ACT_NEW) ? 'Přidat' : 'Uložit změny'; ?>
    </button>
  </div>
</form>