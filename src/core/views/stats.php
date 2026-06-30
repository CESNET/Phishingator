<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
  <h2>Roční statistiky</h2>
  <div class="btn-toolbar mb-2 mb-md-0">
    <a href="<?= $helpLink ?>" target="_blank" class="btn btn-outline-info" role="button">
      <span data-feather="help-circle"></span>
      Nápověda
    </a>
  </div>
</div>

<p>Tato sekce zobrazuje automaticky vygenerovanou, souhrnnou statistiku v&nbsp;jednotlivých letech. Statistika <strong>začíná rokem <?= $statsStartYear ?></strong>, kdy došlo k&nbsp;nasazení systému <i>Phishingator</i>.</p>

<?php for ($year = date('Y'); $year >= $statsStartYear; $year--): ?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
  <h3>Rok <?= $year ?></h3>

  <div class="btn-toolbar mb-2 mb-md-0 align-items-center">
    <button type="button" id="exportDropdown" class="btn btn-info dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
      <span data-feather="download"></span>
      Export dat
    </button>
    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="exportDropdown">
      <h6 class="dropdown-header">Grafy</h6>
      <button type="button" class="dropdown-item d-flex align-items-center justify-content-between dropdown-item export-chart" data-chart="chart-users-responses-<?= $year ?>" data-filename="<?= PHISHING_CAMPAIGN_EXPORT_FILENAME . 's-' . $year ?>-chart-users-responses">
        Reakce uživatelů
        <span class="badge bg-light ml-3">PNG</span>
      </button>
      <button type="button" class="d-flex align-items-center justify-content-between dropdown-item export-chart" data-chart="chart-users-responses-groups-<?= $year ?>" data-filename="<?= PHISHING_CAMPAIGN_EXPORT_FILENAME . 's-' . $year ?>-chart-users-responses-groups">
        Reakce uživatelů dle oddělení
        <span class="badge bg-light ml-3">PNG</span>
      </button>
    </div>
  </div>
</div>

<div class="card-group cards-stats pb-2 mb-3">
  <div class="card bg-light text-center">
    <div class="card-body">
      <h4 class="card-title mb-0">
        <span class="badge-pill badge-dark py-1">
          <?= ${"countCampaigns$year"} ?>

          <?php if (${"countCampaignsDiff$year"} != 0): ?>
          <span class="badge-pill badge-<?= ${"countCampaignsDiffColor$year"} ?> diff cursor-help py-1" title="Rozdíl oproti předcházejícímu roku <?= $year - 1 ?>">
            <?= ${"countCampaignsDiff$year"} ?>
          </span>
          <?php endif; ?>
        </span>
        <br>
        <?= ${"countCampaignsText$year"} ?>
      </h4>
    </div>
  </div>

  <div class="card bg-light text-center">
    <div class="card-body">
      <h4 class="card-title mb-0">
        <span class="badge-pill badge-dark py-1">
          <?= ${"countUsers$year"} ?>

          <?php if (${"countUsersDiff$year"} != 0): ?>
          <span class="badge-pill badge-<?= ${"countUsersDiffColor$year"} ?> diff cursor-help py-1" title="Rozdíl oproti předcházejícímu roku <?= $year - 1 ?>">
            <?= ${"countUsersDiff$year"} ?>
          </span>
          <?php endif; ?>
        </span>
        <br>
        <?= ${"countUsersText$year"} ?>
      </h4>
    </div>
  </div>

  <div class="card bg-light text-center">
    <div class="card-body">
      <h4 class="card-title mb-0">
        <span class="badge-pill badge-dark py-1">
          <?= ${"countVolunteers$year"} ?>

          <?php if (${"countVolunteersDiff$year"} != 0): ?>
          <span class="badge-pill badge-<?= ${"countVolunteersDiffColor$year"} ?> diff cursor-help py-1" title="Rozdíl oproti předcházejícímu roku <?= $year - 1 ?>">
            <?= ${"countVolunteersDiff$year"} ?>
          </span>
          <?php endif; ?>
        </span>
        <br>
        <?= ${"countVolunteersText$year"} ?>
      </h4>
    </div>
  </div>

  <div class="card bg-light text-center">
    <div class="card-body">
      <h4 class="card-title mb-0">
        <span class="badge-pill badge-dark py-1">
          <?= ${"countSentEmails$year"} ?>

          <?php if (${"countSentEmailsDiff$year"} != 0): ?>
          <span class="badge-pill badge-<?= ${"countSentEmailsDiffColor$year"} ?> diff cursor-help py-1" title="Rozdíl oproti předcházejícímu roku <?= $year - 1 ?>">
            <?= ${"countSentEmailsDiff$year"} ?>
          </span>
          <?php endif; ?>
        </span>
        <br>
        <?= ${"countSentEmailsText$year"} ?>
      </h4>
    </div>
  </div>

  <div class="card bg-light text-center">
    <div class="card-body">
      <h4 class="card-title mb-0">
        <span class="badge-pill badge-dark py-1">
          <?= ${"countPhishingWebsites$year"} ?>

          <?php if (${"countPhishingWebsitesDiff$year"} != 0): ?>
          <span class="badge-pill badge-<?= ${"countPhishingWebsitesDiffColor$year"} ?> diff cursor-help py-1" title="Rozdíl oproti předcházejícímu roku <?= $year - 1 ?>">
            <?= ${"countPhishingWebsitesDiff$year"} ?>
          </span>
          <?php endif; ?>
        </span>
        <br>
        <?= ${"countPhishingWebsitesText$year"} ?>
      </h4>
    </div>
  </div>
</div>

<div class="d-flex flex-wrap justify-content-around mt-5">
  <div class="chart-wrapper">
    <h3>Reakce uživatelů na cvičný phishing</h3>
    <p>Konečné reakce uživatelů na všechny phishingové kampaně v&nbsp;roce <?= $year ?>.</p>
    <canvas class="my-4" id="chart-users-responses-<?= $year ?>"></canvas>
  </div>

  <div class="chart-wrapper">
    <h3>Reakce uživatelů dle oddělení [%]</h3>
    <p>Konečné reakce uživatelů podle oddělení na všechny phishingové kampaně v&nbsp;roce <?= $year ?>.</p>
    <div class="table-responsive">
      <canvas class="my-4" id="chart-users-responses-groups-<?= $year ?>"></canvas>
    </div>
  </div>
</div>
<?php endfor; ?>

<script src="/assets/vendor/chartjs/chart.umd.min.js?4.5.1" nonce="<?= HTTP_HEADER_CSP_NONCE ?>"></script>
<script src="/assets/vendor/chartjs/chartjs-plugin-datalabels.min.js?2.2.0" nonce="<?= HTTP_HEADER_CSP_NONCE ?>"></script>
<script nonce="<?= HTTP_HEADER_CSP_NONCE ?>">
  <?php for ($year = date('Y'); $year >= $statsStartYear; $year--): ?>
  let chartUsersResponses<?= $year ?> = new Chart(document.getElementById('chart-users-responses-<?= $year ?>'), {
    plugins: [ChartDataLabels],
    type: 'doughnut',
    data: {
      labels: [<?= $_chartLegend ?>],
      datasets: [{
        data: [<?= ${"chartDataUsersResponses$year"} ?>],
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

  let chartUsersResponsesGroups<?= $year ?> = new Chart(document.getElementById('chart-users-responses-groups-<?= $year ?>'), {
    <?php if (${"barChartLegendDisplay$year"}): ?>plugins: [ChartDataLabels],<?php endif; ?>
    type: 'bar',
    data: {
      labels: [<?= ${"_barChartLegendDesc$year"} ?>],
      datasets: [
        <?php for ($i = 1; $i <= count($barChartLegend); $i++): ?>
        {
          label: '<?= $barChartLegend[$i] ?>',
          data: [<?= ${"barChartLegendData$year"}[$i] ?? 0 ?>],
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
            <?php if (!${"barChartLegendDisplay$year"}): ?>
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
  <?php endfor; ?>
</script>