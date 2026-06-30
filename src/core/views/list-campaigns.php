<?php if (count($campaigns) > 0): ?>
<script src="/assets/vendor/table-sort.min.js" nonce="<?= HTTP_HEADER_CSP_NONCE ?>"></script>

<div class="table-responsive">
  <table class="table table-striped table-hover records-list table-sort table-arrows">
    <thead>
      <tr>
        <th scope="col" class="order-by-asc">#</th>
        <th scope="col">Název</th>
        <th scope="col" class="data-sort">Přidáno</th>
        <th scope="col" class="minw-5-rem">Přidal</th>
        <th scope="col" colspan="2">Podvodný e-mail</th>
        <th scope="col" colspan="2" class="minw-10-rem">Podvodná stránka</th>
        <th scope="col" class="minw-110-px">Příjemců</th>
        <th scope="col" class="data-sort minw-110-px">Aktivní od</th>
        <th scope="col" class="data-sort minw-110-px">Aktivní do</th>
        <th scope="col">Stav</th>
        <?php if ($displayTicketIdColumn): ?>
        <th scope="col" class="minw-8-rem">RT kampaně</th>
        <?php endif; ?>
        <th scope="col" colspan="3" class="disable-sort"></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($campaigns as $campaign): ?>
      <tr>
        <td>
          <abbr title="Identifikátor v aplikaci Phishingator" class="initialism"><?= $campaign['id_campaign'] ?></abbr>
        </td>
        <td><?= $campaign['name'] ?></td>
        <td data-sort="<?= $campaign['date_added'] ?>"><?= insert_nonbreaking_spaces($campaign['date_added_formatted']) ?></td>
        <td>
          <?php if (PermissionsModel::getUserRole() == PERMISSION_ADMIN): ?>
          <a href="/portal/users/<?= ACT_EDIT . '/' . $campaign['id_by_user'] ?>">
          <?php endif; ?>
          <span class="badge badge-<?= $campaign['role_color'] ?>">
            <?= $campaign['username'] ?>
          </span>
          <?php if (PermissionsModel::getUserRole() == PERMISSION_ADMIN): ?>
          </a>
          <?php endif; ?>
        </td>
        <td>
          <?= $campaign['email_name'] ?>
        </td>
        <td class="pl-0">
          <a href="/portal/phishing-emails/<?= ACT_PREVIEW . '/' . $campaign['id_email'] ?>" class="badge badge-secondary" role="button" title="Náhled">
            <span data-feather="eye"></span>
          </a>
        </td>
        <td>
          <span class="badge badge-<?= $campaign['url_protocol_color'] ?>">
            <?= $campaign['url_protocol'] ?>
          </span>://<?= get_hostname_from_url($campaign['url_protocol'] . $campaign['url']) ?>
        </td>
        <td class="pl-0">
          <a href="/portal/phishing-websites/<?= ACT_PREVIEW . '/' . $campaign['id_website'] ?>" target="_blank" class="badge badge-secondary" role="button" title="Náhled">
            <span data-feather="eye"></span>
          </a>
        </td>
        <td><?= $campaign['count_recipients'] ?></td>
        <td data-sort="<?= $campaign['datetime_active_since'] ?>">
          <?= insert_nonbreaking_spaces($campaign['date_active_since_formatted']) ?><br>
          <?= $campaign['time_active_since_formatted'] ?>
        </td>
        <td data-sort="<?= $campaign['datetime_active_to'] ?>">
          <?= insert_nonbreaking_spaces($campaign['date_active_to_formatted']) ?><br>
          <?= $campaign['time_active_to_formatted'] ?>
        </td>
        <td>
          <span class="badge badge-<?= $campaign['status_color'] ?>">
            <?= $campaign['status_text'] ?>
          </span>
        </td>
        <?php if ($displayTicketIdColumn): ?>
        <td>
          <?php if ($campaign['id_ticket']): ?>
            <?php if (!empty(ITS_URL)): ?>
            <a href="<?= ITS_URL . $campaign['id_ticket'] ?>" target="_blank" class="btn btn-outline-secondary btn-sm" role="button">
              <span data-feather="eye"></span>
              RT <?= $campaign['id_ticket'] ?>
            </a>
            <?php else: ?>
            <?= $campaign['id_ticket'] ?>
            <?php endif; ?>
          <?php else: ?>
          &ndash;
          <?php endif; ?>
        </td>
        <?php endif; ?>
        <td>
          <a href="/portal/<?= $urlSection . '/' . ACT_STATS . '/' . $campaign['id_campaign'] ?>" class="btn btn-info btn-sm text-nowrap" role="button">
            <span data-feather="bar-chart-2"></span>
            Statistika
          </a>
        </td>
        <td>
          <form method="post" action="/portal/<?= $urlSection . '/' . ACT_DEL . '/' . $campaign['id_campaign'] ?>" class="d-inline">
            <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">

            <button type="submit" class="btn btn-secondary btn-sm btn-confirm" data-confirm="Opravdu chcete odstranit tento záznam?" title="Odstranit" aria-label="Odstranit">
              <span data-feather="trash"></span>
            </button>
          </form>
        </td>
        <td>
          <a href="/portal/<?= $urlSection . '/' . ACT_EDIT . '/' . $campaign['id_campaign'] ?>" class="btn btn-primary btn-sm" role="button" title="Upravit" aria-label="Upravit">
            <span data-feather="edit-2"></span>
          </a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="14" class="font-italic">
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