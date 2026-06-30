<?php if (count($groups) > 0): ?>
<script src="/assets/vendor/table-sort.min.js" nonce="<?= HTTP_HEADER_CSP_NONCE ?>"></script>

<div class="table-responsive">
  <table class="table table-striped table-hover records-list table-sort table-arrows">
    <thead>
      <tr>
        <th scope="col">Název</th>
        <th scope="col">Oprávnění</th>
        <th scope="col">Popis</th>
        <th scope="col">Zobrazené LDAP skupiny příjemců</th>
        <th scope="col" class="data-sort">Počet uživatelů</th>
        <th scope="col" colspan="2" class="disable-sort"></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($groups as $group): ?>
      <tr>
        <td><?= $group['name'] ?></td>
        <td>
          <span class="badge badge-<?= $group['role_color'] ?>">
            <?= $group['role_name'] ?>
          </span>
        </td>
        <td><?= $group['description'] ?></td>
        <td class="maxw-20-rem text-truncate">
          <?php if (!empty($group['ldap_groups'])): ?>
          <span class="badge badge-secondary cursor-help" title="Počet zobrazovaných LDAP skupin">
            <?= $group['ldap_groups_sum'] ?>
          </span>:
          <?= str_replace(LDAP_GROUPS_DELIMITER, '; ', $group['ldap_groups']) ?>
          <?php endif; ?>
        </td>
        <td data-sort="<?= $group['count_users'] ?>">
          <a href="/portal/users?group=<?= $group['id_user_group'] ?>" class="btn btn-info btn-sm" role="button">
            <span data-feather="users"></span>
            Seznam uživatelů
            <span class="badge badge-light cursor-help" title="Počet uživatelů v této skupině">
              <?= $group['count_users'] ?>
            </span>
          </a>
        </td>
        <td>
          <?php if (!empty($group['id_parent_group'])): ?>
          <form method="post" action="/portal/<?= $urlSection . '/' . ACT_DEL . '/' . $group['id_user_group'] ?>" class="d-inline">
            <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">

            <button type="submit" class="btn btn-secondary btn-sm btn-confirm" data-confirm="Opravdu chcete odstranit tuto skupinu?<?= (($group['count_users'] > 0) ? ' Zbývající uživatelé budou přeřazeni do rodičovské skupiny.' : '') ?>" title="Odstranit" aria-label="Odstranit">
              <span data-feather="trash"></span>
            </button>
          </form>
          <?php endif; ?>
        </td>
        <td>
          <a href="/portal/<?= $urlSection . '/' . ACT_EDIT . '/' . $group['id_user_group'] ?>" class="btn btn-primary btn-sm" role="button" title="Upravit" aria-label="Upravit">
            <span data-feather="edit-2"></span>
          </a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="8" class="font-italic">
          <?= $countRecordsText ?>
        </td>
      </tr>
    </tfoot>
  </table>
</div>
<?php endif; ?>
