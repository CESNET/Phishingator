<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
  <h2>Úvodní stránka</h2>
  <div class="btn-toolbar mb-2 mb-md-0">
    <a href="<?= $helpLink ?>" target="_blank" class="btn btn-outline-info" role="button">
      <span data-feather="help-circle"></span>
      Nápověda
    </a>
  </div>
</div>

<p>Vítejte v&nbsp;systému pro <strong>rozesílání cvičných phishingových zpráv</strong>.</p>

<?php if (PermissionsModel::getUserPermission() == PERMISSION_ADMIN || PermissionsModel::getUserPermission() == PERMISSION_TEST_MANAGER): ?>
<div class="alert alert-info" role="alert">
  <strong>Menu</strong>, které máte k&nbsp;dispozici, je generováno v&nbsp;závislosti na tom, <strong>jaké oprávnění</strong> máte právě nastaveno &ndash; přepnout mezi jiným oprávněním je možné v&nbsp;<strong>pravé horní části</strong> obrazovky.
</div>
<?php endif; ?>

<div class="card-group cards-stats pb-2 mb-3">
  <?php if (PermissionsModel::getUserRole() == PERMISSION_ADMIN): ?>
  <div class="card bg-light text-center">
    <a href="/portal/campaigns">
      <div class="card-body">
        <h4 class="card-title mb-0">
          <span class="badge-pill badge-dark py-1"><?= $countCampaigns ?></span><br>
          <?= $countCampaignsText ?>
        </h4>
      </div>
    </a>
  </div>

  <div class="card bg-light text-center">
    <a href="/portal/users">
      <div class="card-body">
        <h4 class="card-title mb-0">
          <span class="badge-pill badge-dark py-1"><?= $countUsers ?></span><br>
          <?= $countUsersText ?>
        </h4>
      </div>
    </a>
  </div>

  <div class="card bg-light text-center">
    <a href="/portal/users?records=<?= $countVolunteers ?>&amp;only-volunteers=1">
      <div class="card-body">
        <h4 class="card-title mb-0">
          <span class="badge-pill badge-dark py-1"><?= $countVolunteers ?></span><br>
          <?= $countVolunteersText ?>
        </h4>
      </div>
    </a>
  </div>

  <div class="card bg-light text-center">
    <a href="/portal/phishing-emails">
      <div class="card-body">
        <h4 class="card-title mb-0">
          <span class="badge-pill badge-dark py-1"><?= $countSentEmails ?></span><br>
          <?= $countSentEmailsText ?>
        </h4>
      </div>
    </a>
  </div>

  <div class="card bg-light text-center">
    <a href="/portal/phishing-websites">
      <div class="card-body">
        <h4 class="card-title mb-0">
          <span class="badge-pill badge-dark py-1"><?= $countPhishingWebsites ?></span><br>
          <?= $countPhishingWebsitesText ?>
        </h4>
      </div>
    </a>
  </div>

  <?php elseif (PermissionsModel::getUserRole() == PERMISSION_TEST_MANAGER): ?>

  <div class="card bg-light text-center">
    <a href="/portal/campaigns">
      <div class="card-body">
        <h4 class="card-title mb-0">
          <span class="badge-pill badge-dark py-1"><?= $countCampaigns ?></span><br>
          <?= $countCampaignsText ?>
        </h4>
      </div>
    </a>
  </div>

  <div class="card bg-light text-center">
    <a href="/portal/phishing-emails">
      <div class="card-body">
        <h4 class="card-title mb-0">
          <span class="badge-pill badge-dark py-1"><?= $countSentEmails ?></span><br>
          <?= $countSentEmailsText ?>
        </h4>
      </div>
    </a>
  </div>

  <?php else: ?>

  <div class="card bg-light text-center">
    <a href="/portal/recieved-phishing-emails">
      <div class="card-body">
        <h4 class="card-title mb-0">
          <span class="badge-pill badge-dark py-1"><?= $countRecievedEmails ?></span><br>
          <?= $countRecievedEmailsText ?>
        </h4>
      </div>
    </a>
  </div>

  <?php if ($countRecievedEmails > 0): ?>
  <div class="card bg-light text-center">
    <div class="card-body">
      <h4 class="card-title mb-0">
        <span class="badge-pill badge-dark py-1"><?= $countSuccessRate ?>%</span><br>
        úspěšnost v&nbsp;odhalování phishingu
      </h4>
    </div>
  </div>
  <?php endif; ?>

  <?php endif; ?>
</div>

<hr>

<div class="d-flex flex-wrap justify-content-around">
  <?php if (PermissionsModel::getUserRole() == PERMISSION_ADMIN): ?>
  <div class="chart-wrapper">
    <h3>Reakce uživatelů na cvičný phishing</h3>
    <p>Konečné reakce uživatelů na všechny phishingové kampaně.</p>
    <canvas class="my-4" id="chart-users-responses"></canvas>
  </div>

  <div class="chart-wrapper">
    <h3>Reakce uživatelů dle oddělení [%]</h3>
    <p>Konečné reakce uživatelů podle oddělení na všechny phishingové kampaně.</p>
    <div class="table-responsive">
      <canvas class="my-4" id="chart-users-responses-groups"></canvas>
    </div>
  </div>

  <div class="chart-wrapper-vertical">
    <h3>Dobrovolníci dle oddělení</h3>
    <p>Zájem uživatelů o&nbsp;odebírání cvičného phishingu.</p>
    <canvas class="my-4" id="chart-volunteers"></canvas>
  </div>

  <?php elseif (PermissionsModel::getUserRole() == PERMISSION_TEST_MANAGER): ?>

  <div class="chart-wrapper">
    <h3>Reakce uživatelů na cvičný phishing</h3>
    <p>Konečné reakce uživatelů na všechny phishingové kampaně, ke kterým mám oprávnění.</p>
    <canvas class="my-4" id="chart-users-responses"></canvas>
  </div>

  <div class="chart-wrapper">
    <h3>Reakce uživatelů dle oddělení [%]</h3>
    <p>Konečné reakce uživatelů podle oddělení na všechny phishingové kampaně, ke kterým mám oprávnění.</p>
    <div class="table-responsive">
      <canvas class="my-4" id="chart-users-responses-groups"></canvas>
    </div>
  </div>

  <?php else: ?>

  <div class="chart-wrapper">
    <h3>Moje souhrnné reakce na cvičný phishing</h3>
    <p>Konkrétní reakce na každý e-mail ukazuje stránka <a href="/portal/recieved-phishing-emails">přijaté phishingové e-maily</a>.</p>
    <canvas class="my-4" id="chart-users-responses"></canvas>
  </div>
  <?php endif; ?>
</div>

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
          anchor: 'end', clamp: 'true'
        }
      }]
    },
    options: {
      responsive: true,
      aspectRatio: 2,
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
      },
      layout: {
        padding: 10
      },
    }
  });
  <?php if (PermissionsModel::getUserRole() <= PERMISSION_TEST_MANAGER): ?>

  let chartUsersResponsesGroups = new Chart(document.getElementById('chart-users-responses-groups'), {
    <?php if ($barChartLegendDisplay): ?>plugins: [ChartDataLabels],<?php endif; ?>
    type: 'bar',
    data: {
      labels: [<?= $_barChartLegendDesc ?>],
      datasets: [
        <?php for ($i = 1; $i <= count($barChartLegend); $i++): ?>
        {
          label: '<?= $barChartLegend[$i] ?>',
          data: [<?= $barChartLegendData[$i] ?? 0 ?>],
          backgroundColor: '#<?= $barChartLegendColors[$i] ?>'
        },
        <?php endfor; ?>
      ]
    },
    options: {
      responsive: false,
      tooltips: {mode: 'index', intersect: false},
      maintainAspectRatio: true,
      aspectRatio: 1.9,
      plugins: {
        legend: {position: 'bottom'},
        datalabels: {
          color: '#fff',
          rotation: -40,
          display: function(context) {
            return context.dataset.data[context.dataIndex] > 10;
          },
          formatter: Math.round
        }
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
          stacked: true, min: 0, max: 100, ticks: {callback: function(value, index, values) {return value + ' %';}}
        }
      },
    }
  });

  <?php endif; if (PermissionsModel::getUserRole() == PERMISSION_ADMIN): ?>
  let chartVolunteers = new Chart(document.getElementById('chart-volunteers'), {
    plugins: [ChartDataLabels],
    type: 'bar',
    data: {
      labels: [<?= $_chartVolunteers ?>],
      datasets: [{
        label: 'Počet dobrovolníků',
        backgroundColor: '#00c851',
        data: [<?= $chartVolunteersData ?>]
      }]
    },
    options: {
      responsive: true,
      tooltips: {mode: 'index', intersect: false},
      indexAxis: 'y',
      maintainAspectRatio: false,
      plugins: {
        legend: {display: false},
        datalabels: {
          color: '#fff',
          display: function(context) {
            return context.dataset.data[context.dataIndex] > 1;
          }
        }
      }
    }
  });
  <?php endif; ?>
</script>