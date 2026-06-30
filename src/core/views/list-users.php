<hr>

<form method="get" id="search-form">
  <div class="row">
    <div class="col-xl-14">
      <div class="row">
        <div class="col mb-3">
          <div class="input-group input-group-sm flex-nowrap">
            <div class="input-group-prepend">
              <label for="records" class="input-group-text">Záznamů na stránku (z&nbsp;<?= $countRecords ?>)</label>
            </div>

            <input type="text" name="records" id="records" class="form-control w-50-px" maxlength="3" value="<?= $countRecordsOnPage ?>">
          </div>
        </div>

        <div class="col mb-3">
          <div class="input-group input-group-sm flex-nowrap">
            <div class="input-group-prepend">
              <label for="find" class="input-group-text">E-mail</label>
            </div>

            <input type="text" name="find" id="find" class="form-control minw-8-rem" value="<?= $filterFind ?>">
          </div>
        </div>

        <div class="col mb-3">
          <div class="input-group input-group-sm flex-nowrap">
            <div class="input-group-prepend">
              <label for="group" class="input-group-text">Skupina</label>
            </div>

            <select name="group" id="group" class="custom-select minw-8-rem btn-submit" data-form="#search-form">
              <option value="0">Vše</option>
              <?php foreach ($groups as $group): ?>
              <option value="<?= $group['id_user_group'] ?>"<?= (($filterGroup == $group['id_user_group']) ? ' selected': ''); ?>><?= $group['name'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="col mb-3">
          <div class="input-group input-group-sm flex-nowrap">
            <div class="input-group-prepend">
              <label for="permission" class="input-group-text">Oprávnění</label>
            </div>

            <select name="permission" id="permission" class="custom-select minw-8-rem btn-submit" data-form="#search-form">
              <option value="0">Vše</option>
              <?php foreach ($permissions as $permission): ?>
              <option value="<?= $permission['id_user_role'] ?>"<?= (($filterPermission == $permission['id_user_role']) ? ' selected': ''); ?>><?= $permission['name'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="col mb-3">
          <div class="input-group input-group-sm flex-nowrap">
            <div class="input-group-prepend">
              <label for="only-volunteers" class="input-group-text">Pouze dobrovolníci</label>
            </div>

            <div class="input-group-text bg-light input-group-checkbox">
              <input type="checkbox" id="only-volunteers" name="only-volunteers" value="1"<?= (($filterOnlyVolunteers) ? ' checked' : '') ?> class="btn-submit" data-form="#search-form">
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-2 mb-3 text-right">
      <button type="submit" class="btn btn-sm btn-outline-secondary">
        <span data-feather="filter"></span>
        Filtrovat
      </button>
    </div>
  </div>
</form>

<?php if (count($users) > 0): ?>
<script src="/assets/vendor/table-sort.min.js" nonce="<?= HTTP_HEADER_CSP_NONCE ?>"></script>

<div class="table-responsive">
  <table class="table table-sm table-striped table-hover records-list table-sort table-arrows">
    <thead>
      <tr>
        <th scope="col">Jméno a&nbsp;příjmení</th>
        <th scope="col">E-mail</th>
        <th scope="col" class="data-sort datetime-short">Registrován</th>
        <th scope="col">Způsob</th>
        <th scope="col" class="data-sort datetime">Poslední přihlášení</th>
        <th scope="col" class="data-sort minw-110-px">Dobrovolník</th>
        <th scope="col" class="data-sort">Úspěšnost</th>
        <th scope="col">Odesláno e-mailů</th>
        <th scope="col" class="data-sort">Limit e-mailů</th>
        <th scope="col">Skupina</th>
        <th scope="col" colspan="2" class="disable-sort"></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $user): ?>
      <tr>
        <td>
          <?php if (!empty($user['person_name'])): ?>
          <?= $user['person_name'] ?>
          <?php else: ?>
          &ndash;
          <?php endif; ?>
        </td>
        <td><?= $user['email'] ?></td>
        <td data-sort="<?= $user['date_added'] ?>"><?= $user['date_added_formatted'] ?></td>
        <td data-sort="<?= $user['voluntary'] ?>">
          <span class="badge badge-<?= $user['voluntary_registration_color'] ?>">
            <?= $user['voluntary_registration'] ?>
          </span>
        </td>
        <td data-sort="<?= $user['date_login'] ?>"><?= $user['date_login_formatted'] ?></td>
        <td data-sort="<?= $user['date_participation'] ?>">
          <span class="badge badge-<?= $user['recieve_email_color'] ?>">
            <?= $user['recieve_email_text'] ?>
          </span>
          <small><?= $user['date_participation_formatted'] ?></small>
        </td>
        <td data-sort="<?= $user['success_rate'] ?>">
          <a href="/portal/<?= $urlSection . '/' . ACT_EDIT . '/' . $user['id_user'] . '#' . ACT_STATS ?>" class="badge badge-<?= $user['success_rate_color'] ?>" role="button">
            <?= $user['success_rate'] ?>&nbsp;%
          </a>
        </td>
        <td><?= $user['recieved_emails_count'] ?></td>
        <td data-sort="<?= $user['email_limit'] ?>"><?= $user['email_limit_formatted'] ?></td>
        <td>
          <a href="/portal/user-groups/<?= ACT_EDIT . '/' . $user['id_user_group'] ?>" class="badge badge-<?= $user['group_color'] ?>" role="button">
            <?= $user['name'] ?>
          </a>
        </td>
        <td>
          <?php if ($user['id_user'] != PermissionsModel::getUserId()): ?>
          <form method="post" action="/portal/<?= $urlSection . '/' . ACT_DEL . '/' . $user['id_user'] ?>" class="d-inline">
            <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">

            <button type="submit" class="btn btn-secondary btn-sm btn-confirm" data-confirm="Opravdu chcete odstranit tento záznam?" title="Odstranit" aria-label="Odstranit">
              <span data-feather="trash"></span>
            </button>
          </form>
          <?php endif; ?>
        </td>
        <td>
          <a href="/portal/<?= $urlSection . '/' . ACT_EDIT . '/' . $user['id_user'] ?>" class="btn btn-primary btn-sm" role="button" title="Upravit" aria-label="Upravit">
            <span data-feather="edit-2"></span>
          </a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<nav aria-label="Další stránky záznamů">
  <ul class="pagination justify-content-center">
    <li class="page-item<?php if (!$prevPageButton): ?> disabled<?php endif; ?>">
      <a class="page-link" href="<?= $_prevPage ?>"<?php if (!$prevPageButton): ?> tabindex="-1"<?php endif; ?>>Předchozí</a>
    </li>

    <li class="page-item">
      <select class="custom-select mr-sm-2 btn-redirect" data-link="<?= $_pageLink ?>">
        <?php for ($i = 1; $i <= $countPages; $i++): ?>
        <option value="<?= $i ?>"<?php if ($page == $i): ?> selected<?php endif; ?>><?= $i ?></option>
        <?php endfor; ?>
      </select>
    </li>

    <li class="page-item<?php if (!$nextPageButton): ?> disabled<?php endif; ?>">
      <a class="page-link" href="<?= $_nextPage ?>"<?php if (!$prevPageButton): ?> tabindex="-1"<?php endif; ?>>Další</a>
    </li>
  </ul>
</nav>
<?php else: ?>
<hr>

<p class="font-italic">Žádné záznamy.</p>
<?php endif; ?>