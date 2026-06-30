<?php if (count($phishingWebsites) > 0): ?>
<script src="/assets/vendor/table-sort.min.js" nonce="<?= HTTP_HEADER_CSP_NONCE ?>"></script>

<div class="table-responsive">
  <table class="table table-striped table-hover records-list table-sort table-arrows">
    <thead>
      <tr>
        <th scope="col" class="order-by-asc">#</th>
        <th scope="col">Název</th>
        <th scope="col" class="data-sort">Přidáno</th>
        <th scope="col" class="minw-5-rem">Přidal</th>
        <th scope="col" class="minw-10-rem">URL</th>
        <th scope="col">Šablona</th>
        <th scope="col" class="minw-5-rem">Stav</th>
        <th scope="col" colspan="4" class="disable-sort"></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($phishingWebsites as $website): ?>
      <tr>
        <td>
          <abbr title="Identifikátor v aplikaci Phishingator" class="initialism"><?= $website['id_website'] ?></abbr>
        </td>
        <td><?= $website['name'] ?></td>
        <td data-sort="<?= $website['date_added'] ?>"><?= $website['date_added_formatted'] ?></td>
        <td>
          <?php if (PermissionsModel::getUserRole() == PERMISSION_ADMIN): ?>
          <a href="/portal/users/<?= ACT_EDIT . '/' . $website['id_by_user'] ?>">
            <?php endif; ?>
            <span class="badge badge-danger">
              <?= $website['username'] ?>
            </span>
            <?php if (PermissionsModel::getUserRole() == PERMISSION_ADMIN): ?>
          </a>
          <?php endif; ?>
        </td>
        <td class="nowrap maxw-20-rem text-truncate">
          <span class="badge badge-<?= $website['url_protocol_color'] ?>">
            <?= $website['url_protocol'] ?>
          </span><?= str_replace(VAR_RECIPIENT_URL, '<code>' . VAR_RECIPIENT_URL . '</code>', $website['url']) ?>
        </td>
        <td><?= $website['template_name'] ?></td>
        <td>
          <span class="badge badge-<?= $website['status_color'] ?>">
            <?= $website['status_text'] ?>
          </span>
        </td>
        <td>
          <?php if ($website['active'] && $website['status'] == 0): ?>
          <a href="/portal/<?= $urlSection . '/' . ACT_PREVIEW . '/' . $website['id_website'] ?>" target="_blank" class="btn btn-info btn-sm" role="button">
            <span data-feather="eye"></span>
            Náhled
          </a>
          <?php endif; ?>
        </td>
        <td>
          <form method="post" action="/portal/<?= $urlSection . '/' . ACT_DUPLICATE . '/' . $website['id_website'] ?>" class="d-inline">
            <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">

            <button type="submit" class="btn btn-secondary btn-sm btn-confirm" data-confirm="Opravdu chcete duplikovat tuto podvodnou stránkou? Duplikovanou stránku je třeba následně ještě manuálně aktivovat.">
              <span data-feather="copy"></span>
              Duplikovat
            </button>
          </form>
        </td>
        <td>
          <form method="post" action="/portal/<?= $urlSection . '/' . ACT_DEL . '/' . $website['id_website'] ?>" class="d-inline">
            <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">

            <button type="submit" class="btn btn-secondary btn-sm btn-confirm" data-confirm="Opravdu chcete odstranit tento záznam?" title="Odstranit" aria-label="Odstranit">
              <span data-feather="trash"></span>
            </button>
          </form>
        </td>
        <td>
          <a href="/portal/<?= $urlSection . '/' . ACT_EDIT . '/' . $website['id_website'] ?>" class="btn btn-primary btn-sm" role="button" title="Upravit" aria-label="Upravit">
            <span data-feather="edit-2"></span>
          </a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="11" class="font-italic">
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