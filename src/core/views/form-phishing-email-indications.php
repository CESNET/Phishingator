<hr>

<div class="container email-preview">
  <div class="row mb-3 mb-sm-2">
    <div class="col-sm-3 text-sm-right">
      Od:
    </div>
    <div class="col-10 text-monospace">
      <?= $_phishingEmail['sender']; ?>
    </div>
  </div>

  <div class="row mb-3 mb-sm-3">
    <div class="col-sm-3 text-sm-right">
      Předmět:
    </div>
    <div class="col-10 text-monospace">
      <strong><?= $_phishingEmail['subject'] ?></strong>
    </div>
  </div>

  <div class="row mb-3 mb-sm-2">
    <div class="col-sm-3"></div>
    <div class="col-10 text-monospace">
      <?= $_phishingEmail['body'] ?>
    </div>
  </div>
</div>

<div class="text-right">
  <a href="/portal/<?= $urlSection . '/' . ACT_PREVIEW . '/' . $phishingEmail['id_email'] ?>" class="btn btn-info" role="button">
    <span data-feather="eye"></span>
    Náhled<span class="d-none d-lg-inline"> včetně indicií</span>
  </a>
</div>

<hr>

<h4>Nová indicie</h4>
<form method="post" action="/portal/<?= $urlSection . '/' . $action . '/' . $phishingEmail['id_email'] ?>">
  <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">

  <div class="form-row">
    <?php $input = 'expression'; ?>
    <div class="form-group col-lg-3">
      <label for="<?= $formPrefix . $input ?>">Indicie (podezřelý řetězec)</label>
      <input type="text" class="form-control" name="<?= $formPrefix . $input ?>" id="<?= $formPrefix . $input ?>" maxlength="<?= $inputsMaxLengths[$input] ?>" value="<?= $inputsValues[$input] ?>" required>
      <small class="form-text text-muted">Pro označení jména odesílatele lze použít proměnnou <code class="replace-variable cursor-pointer" data-input="#<?= $formPrefix . $input ?>" data-var="<?= VAR_INDICATION_SENDER_NAME ?>"><?= VAR_INDICATION_SENDER_NAME ?></code>, pro e-mail odesílatele <code class="replace-variable cursor-pointer" data-input="#<?= $formPrefix . $input ?>" data-var="<?= VAR_INDICATION_SENDER_EMAIL ?>"><?= VAR_INDICATION_SENDER_EMAIL ?></code> a&nbsp;pro předmět pak <code class="replace-variable cursor-pointer" data-input="#<?= $formPrefix . $input ?>" data-var="<?= VAR_INDICATION_SUBJECT ?>"><?= VAR_INDICATION_SUBJECT ?></code>.</small>
    </div>

    <?php $input = 'title'; ?>
    <div class="form-group col-lg-3">
      <label for="<?= $formPrefix . $input ?>">Nadpis</label>
      <input type="text" name="<?= $formPrefix . $input ?>" id="<?= $formPrefix . $input ?>" class="form-control" maxlength="<?= $inputsMaxLengths[$input] ?>" value="<?= $inputsValues[$input] ?>" required>
    </div>

    <?php $input = 'description'; ?>
    <div class="form-group col-lg-7">
      <label for="<?= $formPrefix . $input ?>">Popis (nepovinné)</label>
      <input type="text" name="<?= $formPrefix . $input ?>" id="<?= $formPrefix . $input ?>" class="form-control" maxlength="<?= $inputsMaxLengths[$input] ?>" value="<?= $inputsValues[$input] ?>">
    </div>

    <div class="form-group col-md-3 text-right">
      <label>&nbsp;</label><br>
      <button type="submit" name="<?= $formPrefix . ACT_NEW ?>" class="btn btn-primary">
        <span data-feather="plus"></span>
        Přidat
      </button>
    </div>
  </div>
</form>

<?php if (count($emailIndications) > 0): ?>
<hr>

<h4>Indicie (<?= count($emailIndications) ?>) k&nbsp;rozpoznání tohoto phishingu</h4>
<?php foreach ($emailIndications as $i => $indication): $i++; ?>
<form method="post" action="/portal/<?= $urlSection . '/' . ACT_INDICATIONS . '/' . $phishingEmail['id_email'] ?>" id="indication-<?= $indication['id_indication'] ?>-text" class="mark-indication" data-indication="<?= $indication['id_indication'] ?>">
  <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
  <input type="hidden" name="<?= $formPrefix . ACT_EDIT ?>-id" value="<?= $indication['id_indication'] ?>">

  <div class="form-row">
    <?php $input = 'expression'; ?>
    <div class="form-group col-lg-3">
      <label for="<?= $formPrefix . $input . '-' . $i ?>">Indicie (podezřelý řetězec)</label>
      <input type="text" class="form-control" id="<?= $formPrefix . $input . '-' . $i ?>" name="<?= $formPrefix . ACT_EDIT . '-' . $input ?>" maxlength="<?= $inputsMaxLengths[$input] ?>" value="<?= $indication[$input] ?>" required>
    </div>

    <?php $input = 'title'; ?>
    <div class="form-group col-lg-3">
      <label for="<?= $formPrefix . $input . '-' . $i ?>">Nadpis</label>
      <input type="text" name="<?= $formPrefix . ACT_EDIT . '-' . $input ?>" id="<?= $formPrefix . $input . '-' . $i ?>" class="form-control" maxlength="<?= $inputsMaxLengths[$input] ?>" value="<?= $indication[$input] ?>" required>
    </div>

    <?php $input = 'description'; ?>
    <div class="form-group col-lg-7">
      <label for="<?= $formPrefix . $input . '-' . $i ?>">Popis (nepovinné)</label>
      <input type="text" name="<?= $formPrefix . ACT_EDIT . '-' . $input ?>" id="<?= $formPrefix . $input . '-' . $i ?>" class="form-control" maxlength="<?= $inputsMaxLengths[$input] ?>" value="<?= $indication[$input] ?>">
    </div>

    <div class="form-group col-lg-3 text-right">
      <label>&nbsp;</label><br>

      <button type="submit" name="<?= $formPrefix . ACT_EDIT ?>" class="btn btn-primary float-right ml-1">
        <span data-feather="edit-2"></span>
        Uložit změny
      </button>

      <button type="submit" name="<?= $formPrefix . ACT_DEL ?>" class="btn btn-secondary btn-confirm" data-confirm="Opravdu chcete odstranit tento záznam?">
        <span data-feather="trash"></span>
        Smazat
      </button>
    </div>
  </div>
</form>
<?php endforeach; ?>
<?php endif; ?>