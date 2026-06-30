<hr>

<?php if ($campaign['status'] == 'running' || $campaign['status'] == 'ending'): ?>
<div class="alert alert-with-icon alert-warning" role="alert">
  <div class="alert-icon pr-1">
    <span data-feather="activity"></span>
  </div>
  <div>
    <h4 class="alert-heading">Pozor, kampaň stále běží!</h4>
    Zobrazená data jsou průběžné výsledky, nikoliv konečné.
  </div>
</div>

<hr>
<?php endif; ?>

<h3 class="mb-3">Základní informace</h3>
<div class="table-responsive mb-5">
  <table class="table table-striped table-hover">
    <thead>
      <tr>
        <th>Název</th>
        <th>Přidáno</th>
        <th>Přidal</th>
        <th colspan="2">Podvodný e-mail</th>
        <th colspan="2">Podvodná stránka</th>
        <th>E-mailů odesláno</th>
        <th>Aktivní od</th>
        <th>Aktivní do</th>
        <th>Stav</th>
        <?php if ($campaign['id_ticket']): ?>
        <th>RT kampaně</th>
        <?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><?= $campaign['name'] ?></td>
        <td><?= insert_nonbreaking_spaces($campaign['date_added']) ?></td>
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
        <td><?= $campaign['email_name'] ?></td>
        <td class="pl-0">
          <a href="/portal/phishing-emails/<?= ACT_PREVIEW . '/' . $campaign['id_email'] ?>" class="badge badge-secondary" title="Náhled">
            <span data-feather="eye"></span>
          </a>
        </td>
        <td>
          <?= $campaign['website_name'] ?><br>
          <span class="badge badge-<?= $campaign['url_protocol_color'] ?>"><?= $campaign['url_protocol'] ?></span><?= str_replace(VAR_RECIPIENT_URL, '<code>' . VAR_RECIPIENT_URL . '</code>', $campaign['url']) ?>
        </td>
        <td class="pl-0">
          <a href="/portal/phishing-websites/<?= ACT_PREVIEW . '/' . $campaign['id_website'] ?>" target="_blank" class="badge badge-secondary" title="Náhled">
            <span data-feather="eye"></span>
          </a>
        </td>
        <td>
          <?= $campaign['sent_emails'] . '/' . $campaign['count_recipients'] ?>
        </td>
        <td>
          <?= insert_nonbreaking_spaces($campaign['date_active_since_formatted']) ?><br>
          <?= $campaign['time_active_since'] ?>
        </td>
        <td>
          <?= insert_nonbreaking_spaces($campaign['date_active_to_formatted']) ?><br>
          <?= $campaign['time_active_to'] ?>
        </td>
        <td>
          <span class="badge badge-<?= $campaign['status_color'] ?>">
            <?= $campaign['status_text'] ?>
          </span>
        </td>
        <?php if ($campaign['id_ticket']): ?>
        <td>
          <?php if (!empty(ITS_URL)): ?>
          <a href="<?= ITS_URL . $campaign['id_ticket'] ?>" target="_blank" class="btn btn-outline-secondary btn-sm" role="button">
            <span data-feather="eye"></span>
            RT <?= $campaign['id_ticket'] ?>
          </a>
          <?php else: ?>
          <?= $campaign['id_ticket'] ?>
          <?php endif; ?>
        </td>
        <?php endif; ?>
      </tr>
    </tbody>
  </table>
</div>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mt-5 mb-3 border-bottom">
  <h3>Statistika</h3>

  <div class="btn-toolbar mb-2 mb-md-0 align-items-center">
    <button type="button" id="exportDropdown" class="btn btn-info dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
      <span data-feather="download"></span>
      Export dat
    </button>
    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="exportDropdown">
      <h6 class="dropdown-header">Grafy</h6>
      <button type="button" class="dropdown-item d-flex align-items-center justify-content-between dropdown-item export-chart" data-chart="chart-users-responses" data-filename="<?= PHISHING_CAMPAIGN_EXPORT_FILENAME ?>-<?= $campaign['id_campaign'] ?>-chart-users-responses-<?= date('Y-m-d') ?>">
        Reakce uživatelů
        <span class="badge bg-light ml-3">PNG</span>
      </button>
      <button type="button" class="d-flex align-items-center justify-content-between dropdown-item export-chart" data-chart="chart-users-responses-groups" data-filename="<?= PHISHING_CAMPAIGN_EXPORT_FILENAME ?>-<?= $campaign['id_campaign'] ?>-chart-users-responses-groups-<?= date('Y-m-d') ?>">
        Reakce uživatelů dle oddělení
        <span class="badge bg-light ml-3">PNG</span>
      </button>
      <button type="button" class="d-flex align-items-center justify-content-between dropdown-item export-chart" data-chart="chart-users-responses-sum" data-filename="<?= PHISHING_CAMPAIGN_EXPORT_FILENAME ?>-<?= $campaign['id_campaign'] ?>-chart-users-responses-sum-<?= date('Y-m-d') ?>">
        Všechny provedené akce
        <span class="badge bg-light ml-3">PNG</span>
      </button>
      <div class="dropdown-divider"></div>
      <h6 class="dropdown-header">Data</h6>
      <form method="post" action="/portal/<?= $urlSection . '/' . ACT_EXPORT . '/' . $campaign['id_campaign'] ?>?data=users-responses">
        <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">

        <button type="submit" class="btn btn-link dropdown-item d-flex align-items-center justify-content-between w-100">
          Reakce uživatelů
          <span class="badge bg-light ml-3">CSV</span>
        </button>
      </form>
      <form method="post" action="/portal/<?= $urlSection . '/' . ACT_EXPORT . '/' . $campaign['id_campaign'] ?>?data=website-actions">
        <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">

        <button type="submit" class="btn btn-link dropdown-item d-flex align-items-center justify-content-between w-100">
          Akce na podvodné stránce
          <span class="badge bg-light ml-3">CSV</span>
        </button>
      </form>
      <form method="post" action="/portal/<?= $urlSection . '/' . ACT_EXPORT . '/' . $campaign['id_campaign'] ?>?data=users-compromised">
        <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">

        <button type="submit" class="btn btn-link dropdown-item d-flex align-items-center justify-content-between w-100">
          Kompromitované účty
          <span class="badge bg-light ml-3">CSV</span>
        </button>
      </form>
      <form method="post" action="/portal/<?= $urlSection . '/' . ACT_EXPORT . '/' . $campaign['id_campaign'] ?>?data=users-responses-sum">
        <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">

        <button type="submit" class="btn btn-link dropdown-item d-flex align-items-center justify-content-between w-100">
          Počet akcí každého uživatele
          <span class="badge bg-light ml-3">CSV</span>
        </button>
      </form>
      <form method="post" action="/portal/<?= $urlSection . '/' . ACT_EXPORT . '/' . $campaign['id_campaign'] ?>?data=all">
        <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">

        <button type="submit" class="btn btn-link dropdown-item d-flex align-items-center justify-content-between w-100">
          Všechna data v&nbsp;archivu
          <span class="badge bg-light ml-3">ZIP</span>
        </button>
      </form>
    </div>
  </div>
</div>

<div class="card-group mb-5">
  <?php foreach ($statsSummary as $action): ?>
  <div class="card bg-light">
    <div class="card-body py-3">
      <div class="h4 font-weight-bold mb-0"><?= $action['value'] ?></div>
      <div class="small text-muted"><?= $action['name'] ?></div>
      <span class="badge badge-<?= $action['css_color_class'] ?>"><?= $action['percentage'] ?>&nbsp;%</span>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="d-flex flex-wrap justify-content-around mb-4">
  <div class="chart-wrapper sm">
    <h4>Reakce uživatelů</h4>
    <canvas class="mt-4" id="chart-users-responses"></canvas>

    <p class="text-center mt-5">
      <a href="?<?= ACT_STATS_USERS_RESPONSES ?>#list" class="btn btn-lg btn-info mt-5 text-wrap" role="button">
        <span data-feather="archive"></span>
        Tabulka reakcí uživatelů
      </a>
    </p>
  </div>

  <div class="chart-wrapper">
    <h4>Reakce uživatelů dle oddělení</h4>
    <div class="table-responsive">
      <canvas class="mt-4" id="chart-users-responses-groups"></canvas>
    </div>
  </div>

  <div class="chart-wrapper sm">
    <h4>Všechny provedené akce</h4>
    <canvas class="mt-4" id="chart-users-responses-sum"></canvas>

    <p class="text-center mt-5">
      <a href="?<?= ACT_STATS_WEBSITE_ACTIONS ?>#list" class="btn btn-lg btn-info mt-5 text-wrap" role="button">
        <span data-feather="activity"></span>
        Tabulka všech provedených akcí
      </a>
    </p>
  </div>
</div>

<?php if (isset($_GET[ACT_STATS_USERS_RESPONSES]) || isset($_GET[ACT_STATS_WEBSITE_ACTIONS])): ?>
<a id="list"></a>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mt-5 mb-3 border-bottom">
  <h3><?php if (isset($_GET[ACT_STATS_USERS_RESPONSES])): ?>Reakce jednotlivých uživatelů<?php else: ?>Provedené akce na podvodné stránce<?php endif; ?></h3>

  <div class="custom-control custom-checkbox mr-2">
    <form method="post" action="/portal/<?= $urlSection . '/' . ACT_STATS_BLUR_IDENTITIES ?>" id="blur-identities-form">
      <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">

      <input type="checkbox" name="<?= ACT_STATS_BLUR_IDENTITIES ?>" id="blur-identities" class="custom-control-input" data-form="blur-identities-form"<?= ((!empty($blurIdentities)) ? ' checked' : '') ?>>
      <label class="custom-control-label" for="blur-identities">Rozmazat identity</label>
    </form>
  </div>
</div>
<?php endif; ?>

<?php if (isset($_GET[ACT_STATS_USERS_RESPONSES])): ?>

<p>Nejzávažnější akce, kterou může uživatel v&nbsp;kampani provést, je <i>zadání platných (přihlašovacích) údajů</i>.</p>

<div class="row">
  <?php foreach ($usersResponsesLegend as $idAction => $nameAction): ?>
  <div class="col-xl-4">
    <h4>
      <span class="badge badge-<?= $usersResponsesLegendCssClasses[$idAction] ?>"><?= $nameAction ?></span>
    </h4>
    <br>

    <div class="table-responsive">
      <table class="table table-striped table-hover">
        <thead>
          <tr>
            <th>#</th>
            <th>Uživatel</th>
            <th>Pracoviště</th>
            <th colspan="3">Záznamy</th>
          </tr>
        </thead>
        <tbody>
        <?php $i = 1; foreach ($usersResponses as $data): ?>
          <?php if ($data['id_action'] != $idAction) continue; ?>
          <tr class="campaign-user-action">
            <td>
              <small><?= $i++; ?></small>
            </td>
            <td>
              <?php if (PermissionsModel::getUserRole() == PERMISSION_ADMIN): ?>
              <a href="/portal/users/<?= ACT_EDIT . '/' . $data['id_user'] ?>">
                <span class="identity <?= $blurIdentities ?>" title="<?= $data['used_email'] ?>" data-toggle="tooltip"><?= $data['username'] ?></span>
              </a>
              <?php else: ?>
              <abbr class="identity <?= $blurIdentities ?>" title="<?= $data['used_email'] ?>" data-toggle="tooltip"><?= $data['username'] ?></abbr>
              <?php endif; ?>
            </td>
            <td>
              <?php if (CAMPAIGN_STATS_AGGREGATION == 2): ?>
              <small><?= mb_strtoupper(StatsModel::getSubdomainFromEmail($data['used_email'])) ?></small>
              <?php else: ?>
              <small class="d-block maxw-6-rem text-truncate cursor-help" data-toggle="tooltip" data-placement="left" data-html="true" data-original-title="<?= str_replace(LDAP_GROUPS_DELIMITER, '<br>', $data['used_group']) ?>"><?= mb_strtoupper(str_replace(LDAP_GROUPS_DELIMITER, '; ', $data['used_group'])) ?></small>
              <?php endif; ?>
            </td>
            <td>
              <form method="post" action="/portal/<?= $urlSection . '/' . ACT_STATS . '/' . $campaign['id_campaign'] ?>">
                <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">

                <input type="hidden" name="<?= ACT_STATS_REPORT_PHISH ?>" value="<?= $data['id_captured_data'] ?>">

                <div class="text-purple<?php if ($data['reported'] == 0): ?> user-phishing-report<?php endif; ?>" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="Uživatel nahlásil pokus o&nbsp;cvičný phishing<br>(např. na helpdesk, správci IT, &hellip;)">
                  <button type="submit" class="border-0 bg-transparent btn-confirm" data-confirm="Opravdu chcete u uživatele upravit nastavení o nahlášení cvičného phishingu?">
                    <span data-feather="message-circle"></span>
                  </button>
                </div>
              </form>
            </td>
            <td>
              <?php if (isset($testPageData[$data['id_user']])): ?>
              <div class="text-primary cursor-help" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="Uživatel navštívil vzdělávací stránku o&nbsp;phishingu<br><?= $testPageData[$data['id_user']] ?>">
                <span data-feather="flag"></span>
              </div>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($idAction != CAMPAIGN_NO_REACTION_ID): ?>
              <a href="?<?= ACT_STATS_WEBSITE_ACTIONS ?>&amp;rows=<?= $data['id_user'] ?>#d<?= $data['id_captured_data'] ?>" class="text-dark">
                <span data-feather="eye"></span>
              </a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php elseif (isset($_GET[ACT_STATS_WEBSITE_ACTIONS])): ?>

<?php if (count($capturedData) > 0): ?>
<p>Výpis všech akcí provedených na podvodné stránce, která je přístupná ze zasílaného podvodného e-mailu. Zastoupení těchto akcí znázorňuje také graf <i>Všechny provedené akce</i>.</p>

<script src="/assets/vendor/table-sort.min.js" nonce="<?= HTTP_HEADER_CSP_NONCE ?>"></script>

<div class="table-responsive">
  <table class="table table-striped table-hover records-list table-sort table-arrows">
    <thead>
      <tr>
        <th class="order-by-asc">#</th>
        <th colspan="3" class="minw-5-rem">Uživatel</th>
        <th>Akce</th>
        <th class="data-sort datetime">Datum a&nbsp;čas</th>
        <th>IP adresa</th>
        <th class="minw-10-rem maxw-10-rem">Zadané jméno</th>
        <th class="minw-10-rem maxw-10-rem">Zadané heslo</th>
        <th>User agent</th>
      </tr>
    </thead>
    <tbody>
      <?php $i = count($capturedData); foreach ($capturedData as $data): ?>
      <tr<?php if (isset($_GET['rows']) && $_GET['rows'] == $data['id_user']): ?> class="table-primary"<?php endif; ?>>
        <td>
          <a id="d<?= $data['id_captured_data'] ?>" class="hidden-anchor-link"></a>
          <a id="r<?= $i ?>" class="hidden-anchor-link"></a>
          <a href="#r<?= $i ?>" class="text-dark">
            <abbr title="Identifikátor a odkaz na tento záznam v rámci této kampaně" class="initialism cursor-help"><?= $i--; ?></abbr>
          </a>
        </td>
        <td class="pr-0">
          <a href="?<?= ACT_STATS_WEBSITE_ACTIONS ?>&amp;rows=<?= $data['id_user'] ?>#d<?= $data['id_captured_data'] ?>" class="badge badge-secondary" role="button" title="Zvýraznit aktivitu uživatele" data-toggle="tooltip">
            <span data-feather="eye"></span>
          </a>
        </td>
        <td>
          <?php if (PermissionsModel::getUserRole() == PERMISSION_ADMIN): ?>
          <a href="/portal/users/<?= ACT_EDIT . '/' . $data['id_user'] ?>">
            <span class="identity <?= $blurIdentities ?>" title="<?= $data['used_email'] ?>" data-toggle="tooltip"><?= $data['username'] ?></span>
          </a>
          <?php else: ?>
          <abbr class="identity <?= $blurIdentities ?>" title="<?= $data['used_email'] ?>" data-toggle="tooltip"><?= $data['username'] ?></abbr>
          <?php endif; ?>
        </td>
        <td>
          <?php if (CAMPAIGN_STATS_AGGREGATION == 2): ?>
          <small><?= mb_strtoupper(StatsModel::getSubdomainFromEmail($data['used_email'])) ?></small>
          <?php else: ?>
          <small class="d-block maxw-6-rem text-truncate cursor-help" data-toggle="tooltip" data-placement="left" data-html="true" data-original-title="<?= str_replace(LDAP_GROUPS_DELIMITER, '<br>', $data['used_group']) ?>"><?= mb_strtoupper(str_replace(LDAP_GROUPS_DELIMITER, '; ', $data['used_group'])) ?></small>
          <?php endif; ?>
        </td>
        <td>
          <span class="badge badge-<?= $data['css_color_class'] ?>"><?= $data['name'] ?></span>
          <?php if ($data['reported'] == 1): ?>
          <div class="text-success d-inline" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="Uživatel nahlásil pokus o&nbsp;cvičný phishing<br>(např. na helpdesk, lokálnímu správci, &hellip;)">
            <span class="badge badge-purple">ohlášeno</span>
          </div>
          <?php endif; ?>
        </td>
        <td class="text-nowrap" data-sort="<?= $data['visit_datetime'] ?>"><?= $data['visit_datetime_formatted'] ?></td>
        <td>
          <code class="identity <?= $blurIdentities ?> cursor-help" title="<?= $data['ip'] ?>" data-toggle="tooltip">
            <?= $data['ip'] ?>
          </code>
        </td>
        <td class="minw-10-rem maxw-10-rem text-truncate">
          <?php if (!empty($data['filled_username'])): ?>
          <code class="identity <?= $blurIdentities ?> text-primary cursor-help" title="<?= $data['filled_username'] ?>" data-toggle="tooltip" data-placement="left">
            <?= $data['filled_username'] ?>
          </code>
          <?php endif; ?>
        </td>
        <td class="minw-10-rem maxw-10-rem text-truncate">
          <?php if (!empty($data['filled_password'])): ?>
          <code class="identity <?= $blurIdentities ?> text-danger cursor-help" title="<?= $data['filled_password'] ?>" data-toggle="tooltip" data-placement="left">
            <?= $data['filled_password'] ?>
          </code>
          <?php endif; ?>
        </td>
        <td class="maxw-40-rem text-truncate">
          <small class="cursor-help" title="<?= $data['browser_fingerprint'] ?>" data-toggle="tooltip" data-placement="left">
            <?= $data['browser_fingerprint'] ?>
          </small>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php else: ?>
<p>Podvodná stránka zatím nebyla navštívena žádným z&nbsp;příjemců kampaně.</p>
<?php endif; ?>

<?php endif; ?>

<script src="/assets/vendor/chartjs/chart.umd.min.js?4.5.1" nonce="<?= HTTP_HEADER_CSP_NONCE ?>"></script>
<script src="/assets/vendor/chartjs/chartjs-plugin-datalabels.min.js?2.2.0" nonce="<?= HTTP_HEADER_CSP_NONCE ?>"></script>
<script nonce="<?= HTTP_HEADER_CSP_NONCE ?>">
  let chartUsersResponses = new Chart(document.getElementById('chart-users-responses'), {
    plugins: [ChartDataLabels],
    type: 'doughnut',
    data: {
      labels: [<?= $_chartLegend ?>],
      datasets: [{
        data: [<?= $chartDataUsersResponses ?>],
        backgroundColor: [<?= $_chartColors ?>],
        datalabels: {
          anchor: function(context) {
            let totalCount = 0;
            let countLowValues = 0;
            const minValue = 5;

            for (let i = 0; i < context.dataset.data.length; i++) {
              totalCount += context.dataset.data[i];

              if (context.dataset.data[i] < minValue) {
                countLowValues++;
              }
            }

            if (countLowValues > 2 && totalCount > 20 && context.dataset.data[context.dataIndex] < minValue) {
              return context.dataIndex % 2 ? 'start' : 'end';
            }

            return 'end';
          },
          clamp: 'true'
          /*,
          align: function(context) {
            if (context.dataIndex == 0) {
              return 'left';
            }
            else if (context.dataIndex == 1) {
              return 'bottom';
            }
            else if (context.dataIndex == 2) {
              return 'right';
            }
          },
          offset: function(context) {
            if (context.dataIndex == 1) {
              return 45;
            }
          }*/
        }
      }]
    },
    options: {
      responsive: true,
      aspectRatio: 2,
      layout: {padding: {top: 10, left: 15, bottom: 10}},
      plugins: {
        legend: {position: 'right'},
        datalabels: {
          backgroundColor: function(context) {
            return context.dataset.backgroundColor;
          },
          borderColor: '#fff',
          borderRadius: 25,
          borderWidth: 2,
          padding: 5,
          color: '#fff',
          font: {weight: 'bold'},
          textAlign: 'center',
          display: function(context) {
            let dataset = context.dataset;
            let value = dataset.data[context.dataIndex];

            return value > 0;
          },
          formatter: function(value, context) {
            let totalCount = 0;

            for (let i = 0; i < context.dataset.data.length; i++) {
              totalCount += context.dataset.data[i];
            }

            return value + ' / ' + Math.round((value * 100 / totalCount) * 10) / 10 + ' %';
          }
        }
      }}
  });

  let chartUsersResponsesSum = new Chart(document.getElementById('chart-users-responses-sum'), {
    plugins: [ChartDataLabels],
    type: 'doughnut',
    data: {
      labels: [<?= $_chartLegend ?>],
      datasets: [{
        data: [<?= $chartDataUsersResponsesSum ?>],
        backgroundColor: [<?= $_chartColors ?>],
        datalabels: {
          anchor: 'end', clamp: 'true'
        }
      }]
    },
    options: {
      responsive: true,
      aspectRatio: 2,
      layout: {padding: {top: 10, left: 15, bottom: 10}},
      plugins: {
        legend: {position: 'right'},
        datalabels: {
          backgroundColor: function(context) {
            return context.dataset.backgroundColor;
          },
          borderColor: '#fff',
          borderRadius: 25,
          borderWidth: 2,
          padding: 5,
          color: '#fff',
          font: {weight: 'bold'},
          textAlign: 'center',
          display: function(context) {
            let dataset = context.dataset;
            let value = dataset.data[context.dataIndex];

            return value > 0;
          },
          formatter: function(value, context) {
            let totalCount = 0;

            for (let i = 0; i < context.dataset.data.length; i++) {
              totalCount += context.dataset.data[i];
            }

            return value + ' / ' + Math.round((value * 100 / totalCount) * 10) / 10 + ' %';
          }
        }
      }}
  });

  let chartUsersResponsesGroups = new Chart(document.getElementById('chart-users-responses-groups'), {
    type: 'bar',
    data: {
      labels: [<?= $_barChartLegendDesc ?>],
      datasets: [
        <?php for ($i = 1; $i <= count($barChartLegend); $i++): ?>
        {
          label: '<?= $barChartLegend[$i] ?>',
          backgroundColor: '#<?= $barChartLegendColors[$i] ?>',
          data: [<?= $barChartLegendData[$i] ?>]
        },
        <?php endfor; ?>
      ]
    },
    options: {
      responsive: false,
      tooltips: {mode: 'index', intersect: false},
      maintainAspectRatio: true,
      plugins: {
        legend: {position: '<?= (($barChartCols > 1) ? 'bottom' : 'right') ?>'}
      },
      scales: {
        x: {
          stacked: true, ticks: {
            autoSkip: false,
            <?php if (!$barChartLegendDisplay): ?>
            callback: function() { return ''; }
            <?php endif; ?>
          }
        },
        y: {
          stacked: true, ticks: {min: 0, precision: 0}
        }
      }
    }
  });
</script>