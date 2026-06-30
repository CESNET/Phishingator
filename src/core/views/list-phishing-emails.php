<?php if (count($phishingEmails) > 0): ?>
<script src="/assets/vendor/table-sort.min.js" nonce="<?= HTTP_HEADER_CSP_NONCE ?>"></script>

<div class="table-responsive">
  <table class="table table-striped table-hover records-list table-sort table-arrows">
    <thead>
      <tr>
        <th scope="col" class="order-by-asc">#</th>
        <th scope="col">Název</th>
        <th scope="col" class="data-sort">Přidáno</th>
        <th scope="col" class="minw-5-rem">Přidal</th>
        <th scope="col" class="minw-10-rem">Předmět</th>
        <?php if (PermissionsModel::getUserRole() == PERMISSION_ADMIN): ?>
        <th scope="col" colspan="2" class="disable-sort">Znaky phishingu</th>
        <th scope="col" colspan="3" class="disable-sort"></th>
        <?php else: ?>
        <th scope="col" class="disable-sort"></th>
        <?php endif; ?>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($phishingEmails as $email): ?>
      <tr>
        <td>
          <abbr title="Identifikátor v aplikaci Phishingator" class="initialism"><?= $email['id_email'] ?></abbr>
        </td>
        <td>
          <?= $email['name'] ?>
          <?php if ($email['hidden']): ?>
          <span class="badge badge-secondary ml-1">skrytý e-mail</span>
          <?php endif; ?>
        </td>
        <td data-sort="<?= $email['date_added'] ?>"><?= $email['date_added_formatted'] ?></td>
        <td>
          <?php if (PermissionsModel::getUserRole() == PERMISSION_ADMIN): ?>
          <a href="/portal/users/<?= ACT_EDIT . '/' . $email['id_by_user'] ?>">
            <?php endif; ?>
            <span class="badge badge-danger">
              <?= $email['username'] ?>
            </span>
            <?php if (PermissionsModel::getUserRole() == PERMISSION_ADMIN): ?>
          </a>
          <?php endif; ?>
        </td>
        <td><?= $email['subject'] ?></td>
        <?php if (PermissionsModel::getUserRole() == PERMISSION_ADMIN): ?>
        <td class="td-btn pr-0">
          <a href="/portal/<?= $urlSection . '/' . ACT_INDICATIONS . '/' . $email['id_email'] ?>" class="btn btn-info btn-sm mb-2 mb-xl-0" role="button">
            <span data-feather="key"></span>
            Nastavit indicie
            <span class="badge badge-<?= $email['indications_color'] ?> cursor-help" title="Počet indicií pro rozpoznání phishingu přidaných k e-mailu">
              <?= $email['indications_sum'] ?>
            </span>
          </a>
        </td>
        <?php endif; ?>
        <td>
          <a href="/portal/<?= $urlSection . '/' . ACT_PREVIEW . '/' . $email['id_email'] ?>" class="btn btn-info btn-sm" role="button">
            <span data-feather="eye"></span>
            Náhled
          </a>
        </td>
        <?php if (PermissionsModel::getUserRole() == PERMISSION_ADMIN): ?>
        <td>
          <form method="post" action="/portal/<?= $urlSection . '/' . ACT_DUPLICATE . '/' . $email['id_email'] ?>" class="d-inline">
            <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">

            <button type="submit" class="btn btn-secondary btn-sm btn-confirm" data-confirm="Opravdu chcete duplikovat tento podvodný e-mail? Dojde k duplikování jak e-mailu, tak všech přiřazených indicií.">
              <span data-feather="copy"></span>
              Duplikovat
            </button>
          </form>
        </td>
        <td>
          <form method="post" action="/portal/<?= $urlSection . '/' . ACT_DEL . '/' . $email['id_email'] ?>" class="d-inline">
            <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">

            <button type="submit" class="btn btn-secondary btn-sm btn-confirm" data-confirm="Opravdu chcete odstranit tento záznam?" title="Odstranit" aria-label="Odstranit">
              <span data-feather="trash"></span>
            </button>
          </form>
        </td>
        <td>
          <a href="/portal/<?= $urlSection . '/' . ACT_EDIT . '/' . $email['id_email'] ?>" class="btn btn-primary btn-sm" role="button" title="Upravit" aria-label="Upravit">
            <span data-feather="edit-2"></span>
          </a>
        </td>
        <?php endif; ?>
      </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="10" class="font-italic">
          <?= $countRecordsText ?>
        </td>
      </tr>
    </tfoot>
  </table>
</div>
<?php else: ?>
<hr>

<p class="font-italic"><?= $countRecordsText ?></p>
<?php endif; ?>