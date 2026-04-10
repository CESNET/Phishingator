$(function() {
  $('[data-toggle="tooltip"]').tooltip();

  if (window.innerHeight + 50 < document.documentElement.scrollHeight && window.innerWidth >= 768) {
    document.querySelector('.btn-top-page').classList.toggle('d-none');
  }
});


$('.btn-close').on('click', function() {
  window.close();
});

$('.btn-confirm').on('click', function() {
  const message = this.dataset.confirm;

  if (message && !confirm(message)) {
    return false;
  }
});

$('.btn-submit').on('change', function() {
  document.querySelector(this.dataset.form).submit();
});

$('.btn-redirect').on('change', function() {
  window.location.href = this.dataset.link + this.value;
});

$('.btn-toggle-display').on('click', function() {
  document.querySelector(this.dataset.toggle).classList.toggle('d-none');
});


// PHISHING CAMPAIGNS
function extractEmails(emails, separator) {
  return emails.split(separator).map(item => item.trim().toLowerCase()).filter(Boolean);
}

function isEmailValid(input) {
  const validEmailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  return validEmailPattern.test(input);
}

$('.set-preview-btn').on('change', function() {
  const baseLink = this.dataset.previewLink;
  const id = this.querySelector(':checked').value;
  const link = (id > 0) ? baseLink + '/preview/' + id : baseLink;

  document.querySelector(this.dataset.previewBtn).setAttribute('href', link);
});

$('.insert-recipients-emails').on('click', function() {
  const recipientsSeparator = "\n";

  const recipientsTextarea = document.querySelector('#campaign-recipients');
  const recipientsCheckboxes = document.querySelectorAll('#recipientsDialog input[type=checkbox]');

  const recipientsList = new Set(extractEmails(recipientsTextarea.value, recipientsSeparator));

  for (let i = 0; i < recipientsCheckboxes.length; i++) {
    const checkbox = recipientsCheckboxes[i];
    const recipientEmail = checkbox.dataset.emailLowercase;

    if (checkbox.checked && isEmailValid(recipientEmail)) {
      recipientsList.add(recipientEmail);
    }
    else {
      recipientsList.delete(recipientEmail);
    }
  }

  recipientsTextarea.value = Array.from(recipientsList).join(recipientsSeparator);

  updateSumEmails();

  $('#recipientsDialog').modal('hide');
});

$('.remove-recipients-emails').on('click', function() {
  const recipientsSeparator = "\n";

  const recipientsTextarea = document.querySelector('#campaign-recipients');
  const recipientsToRemoveTextarea = document.querySelector('#campaign-remove-recipients');

  const recipientsList = new Set(extractEmails(recipientsTextarea.value, recipientsSeparator));
  const recipientsToRemove = extractEmails(recipientsToRemoveTextarea.value, recipientsSeparator);

  let removeCount = 0;

  for (let i = 0; i < recipientsToRemove.length; i++) {
    const recipientEmail = recipientsToRemove[i];

    if (isEmailValid(recipientEmail) && recipientsList.delete(recipientEmail)) {
      removeCount++;
    }
  }

  if (removeCount === 0) {
    alert('Žádné zadané e-maily nebyly v seznamu příjemců nalezeny.');
  }
  else {
    if (confirm('V seznamu příjemců bylo nalezeno ' + removeCount + ' e-mailů, které budou odstraněny. Opravdu chcete e-maily těchto příjemců odebrat?')) {
      recipientsTextarea.value = Array.from(recipientsList).join(recipientsSeparator);
      syncRecipientsCheckboxes(recipientsTextarea.value);

      $('#removeRecipientsDialog').modal('hide');
    }
  }
});

$('#campaign-recipients').on('change keyup', function() {
  syncRecipientsCheckboxes(this.value);
});

function syncRecipientsCheckboxes(recipients) {
  const recipientsSeparator = "\n";
  const recipientsList = new Set(extractEmails(recipients, recipientsSeparator).filter(email => isEmailValid(email)));

  const checkboxes = document.querySelectorAll('#recipientsDialog input[type="checkbox"]');

  for (let i = 0; i < checkboxes.length; i++) {
    const checkbox = checkboxes[i];

    if (!checkbox.dataset.checkboxesGroup) {
      const recipient = checkbox.dataset.emailLowercase;
      const recipientChecked = recipientsList.has(recipient);

      if (checkbox.checked !== recipientChecked) {
        checkbox.checked = recipientChecked;
        markSameCheckboxes(checkbox);
      }
    }
  }

  updateSumEmails(recipientsList.size);
}

function updateSumEmails(sumEmails = null) {
  const sumEmailsLabel = document.querySelector('#countRecipients');

  if (sumEmails === null) {
    const validEmailPattern = /[^\s@]+@[^\s@]+\.[^\s@]+/g;

    const recipientsTextarea = document.querySelector('#campaign-recipients').value;
    const validEmails = recipientsTextarea.match(validEmailPattern);

    sumEmails = validEmails ? validEmails.length : 0;
  }

  sumEmailsLabel.textContent = sumEmails;
}

$('.mark-all-checkboxes').on('click', function() {
  const recipientsCheckboxes = document.querySelectorAll(this.dataset.checkboxesGroup + ' input[type="checkbox"]');
  let checkedState = false;

  for (let i = 0; i < recipientsCheckboxes.length; i++) {
    if (!recipientsCheckboxes[i].checked) {
      checkedState = true;
      break;
    }
  }

  for (let i = 0; i < recipientsCheckboxes.length; i++) {
    recipientsCheckboxes[i].checked = checkedState;
  }

  updateGroupCheckboxStates();
});

$('.mark-group-checkboxes').on('click', function() {
  const recipientsGroup = document.querySelector(this.dataset.checkboxesGroup);
  const recipientsCheckboxes = recipientsGroup.querySelectorAll('input[type="checkbox"]');

  const checkedRecipients = !recipientsCheckboxes[0]?.checked;

  let checkedCount = 0;

  for (let i = 0; i < recipientsCheckboxes.length; i++) {
    const checkbox = recipientsCheckboxes[i];

    checkbox.checked = checkedRecipients;

    if (checkbox.checked) {
      checkedCount++;
    }

    markSameCheckboxes(checkbox);
  }

  if (recipientsGroup.classList.contains('d-none')) {
    recipientsGroup.classList.remove('d-none');
  }

  updateGroupCheckboxState(this, checkedCount);
});

$('.mark-same-checkboxes').on('click', function() {
  markSameCheckboxes(this);
});

function markSameCheckboxes(recipient) {
  const recipientCheckboxes = document.querySelectorAll(
      '#recipientsDialog input[type="checkbox"][data-email-lowercase="' + recipient.dataset.emailLowercase + '"]'
  );

  for (let i = 0; i < recipientCheckboxes.length; i++) {
    recipientCheckboxes[i].checked = recipient.checked;
  }
}

$('.expand-all-groups').on('click', function() {
  const isPressed = this.getAttribute('aria-pressed') === 'true';

  document.querySelectorAll('.group-recipients').forEach(
      group => group.classList.toggle('d-none', isPressed)
  );

  this.setAttribute('aria-pressed', !isPressed);
});

function updateGroupCheckboxState(groupCheckbox, checkedCount) {
  const totalCheckboxesCount = parseInt(groupCheckbox.dataset.checkboxesGroupTotal || "0");
  const labelCount = document.querySelector('#' + groupCheckbox.id + '-count');

  groupCheckbox.dataset.checkboxesGroupChecked = checkedCount;
  labelCount.textContent = checkedCount + ' / ' + totalCheckboxesCount;

  if (totalCheckboxesCount === checkedCount) {
    groupCheckbox.checked = true;
    groupCheckbox.indeterminate = false;
  }
  else if (checkedCount === 0) {
    groupCheckbox.checked = false;
    groupCheckbox.indeterminate = false;
  }
  else {
    groupCheckbox.checked = false;
    groupCheckbox.indeterminate = true;
  }
}

function updateGroupCheckboxStates() {
  document.querySelectorAll('.mark-group-checkboxes').forEach(groupCheckbox => {
    const group = groupCheckbox.dataset.checkboxesGroup;

    if (group) {
      const checkboxes = document.querySelectorAll(group + ' input[type="checkbox"]');

      let checkedCount = 0;

      for (let i = 0; i < checkboxes.length; i++) {
        if (checkboxes[i].checked) {
          checkedCount++;
        }
      }

      updateGroupCheckboxState(groupCheckbox, checkedCount);
    }
  });
}

$('.select-recipients').on('click', function() {
  updateGroupCheckboxStates();
});

document.querySelectorAll('#recipientsDialog input[type="checkbox"]').forEach(checkbox => {
  checkbox.addEventListener('change', function () {
    updateGroupCheckboxStates();
  });
});

$('.import-recipients').on('click', function() {
  const recipientsSeparator = "\n";
  const recipientsTextarea = document.getElementById('campaign-recipients');

  let recipientsList = new Set(extractEmails(recipientsTextarea.value, recipientsSeparator));
  let importFileInput = document.createElement('input');

  importFileInput.setAttribute('type', 'file');
  importFileInput.addEventListener('change', function() {
    let file = importFileInput.files[0];

    if (file && (file.type === 'text/plain' || file.type === 'text/csv')) {
      let fileReader = new FileReader();

      fileReader.readAsText(file);
      fileReader.onload = function() {
        let importedRecipients = new Set(extractEmails(fileReader.result, /\r?\n/));
        let importedValidRecipients = [];

        if (file.type === 'text/csv') {
          importedRecipients = importValuesFromCSV(Array.from(importedRecipients));
        }

        importedRecipients.forEach(function (recipient) {
          if (isEmailValid(recipient)) {
            let recipientCheckbox = document.querySelector('#recipientsDialog input[value="' + recipient + '" i]');

            if (recipientCheckbox) {
              recipientCheckbox.checked = true;
              markSameCheckboxes(recipientCheckbox);
            }

            importedValidRecipients.push(recipient);
          }
        });

        let allUniqRecipients = [...new Set([...recipientsList, ...importedValidRecipients])];

        recipientsTextarea.value = allUniqRecipients.join(recipientsSeparator);

        updateSumEmails();
      };
    }
    else {
      alert('Vybraný typ souboru není podporován.');
    }
  });

  importFileInput.click();
});

function importValuesFromCSV(lines) {
  let separator = prompt('Zadejte znak, který je v CSV souboru použit jako oddělovač hodnot.', ';');
  let values = [];

  if (separator !== null && separator !== '') {
    values = lines.flatMap(line => line.split(separator).map(item => item.trim()));
  }

  return values;
}

$('#blur-identities').on('click', function() {
  const form = document.getElementById(this.dataset.form);

  document.querySelectorAll('.identity').forEach(identity => {
    identity.classList.toggle('blur-text');
  });

  fetch(form.action, {
    method: 'POST',
    body: new FormData(form)
  });
});

$('.export-chart').on('click', function(e) {
  e.preventDefault();

  const canvas = document.getElementById(this.dataset.chart);

  if (canvas) {
    const chart = Chart.getChart(canvas);

    if (chart) {
      const link = document.createElement('a');

      const multiplier = 2;

      const originalWidth = chart.width;
      const originalHeight = chart.height;
      const originalDPR = chart.options.devicePixelRatio || window.devicePixelRatio;

      chart.resize(originalWidth * multiplier, originalHeight * multiplier);
      chart.options.devicePixelRatio = 3;

      chart.update('none');

      link.href = chart.toBase64Image('image/png');
      link.download = this.dataset.filename + '.png';
      link.click();

      chart.resize(originalWidth, originalHeight);
      chart.options.devicePixelRatio = originalDPR;

      chart.update('none');
    }
  }
});


// VARIABLES
$('.replace-variable').on('click', function() {
  const input = document.querySelector(this.dataset.input);

  if (confirm('Opravdu chcete obsah pole nahradit touto proměnnou?')) {
    input.value = this.dataset.var;
    input.focus();
  }
});

$('.insert-variable').on('click', function() {
  const input = document.querySelector(this.dataset.input);

  input.value = input.value + this.dataset.var;
  input.focus();
});


// PHISHING EMAILS
$('.phishing-email-variables code').on('click', function() {
  const textareaId = 'phishing-email-body';
  const editor = tinymce.get(textareaId);

  let value = this.dataset.var;
  const type = this.dataset.type || 'variable';

  if (editor) {
    const selectedText = editor.selection.getContent({ format: 'text' });

    if (selectedText && type === 'variable') {
      value = value.replace('text', selectedText);
    }

    if (type === 'html') {
      editor.selection.setContent(value + ' ');
    }
    else {
      const variableNode = createVariableNode(value);
      editor.selection.setContent(variableNode.outerHTML + ' ');
    }

    editor.focus();
  }
  else {
    const input = document.getElementById(textareaId);

    const selectedText = input.value.substring(input.selectionStart, input.selectionEnd);

    if (selectedText) {
      value = value.replace('text', selectedText);
    }

    const cursorPos = input.selectionStart;
    const cursorPosAfter = cursorPos + value.length;

    const textBefore = input.value.substring(0, cursorPos);
    const textAfter = input.value.substring(input.selectionEnd);

    input.value = textBefore + value + textAfter;

    setSelectionRange(input, cursorPosAfter, cursorPosAfter);
  }
});

function setSelectionRange(input, selectionStart, selectionEnd) {
  if (input.setSelectionRange) {
    input.focus();
    input.setSelectionRange(selectionStart, selectionEnd);
  }
  else if (input.createTextRange) {
    const range = input.createTextRange();

    range.collapse(true);
    range.moveEnd('character', selectionEnd);
    range.moveStart('character', selectionStart);
    range.select();
  }
}


// PHISHING WEBSITES
$('#phishing-domains-dropdown a').on('click', function() {
  const urlInput = document.querySelector('#phishing-website-url');

  urlInput.value = this.dataset.var;
  urlInput.focus();
});

$('.phishing-domain-protocol').on('click', function() {
  const urlInput = document.querySelector('#phishing-website-url');
  const selectedProtocol = this.dataset.var;

  let url = urlInput.value.trim();

  if (url.startsWith('http://') && selectedProtocol === 'https') {
    url = url.replace('http://', 'https://');
  }
  else if (url.startsWith('https://') && selectedProtocol === 'http') {
    url = url.replace('https://', 'http://');
  }
  else if (!url.startsWith('http://') && !url.startsWith('https://')) {
    url = selectedProtocol + '://' + url;
  }

  urlInput.value = url;
  urlInput.focus();
});


// USER GROUPS
$('.user-groups-role').on('change', function() {
  const groups = document.querySelector('#groups');
  const role = parseInt(this.value);

  if (!isNaN(role) && (role === 1 || role === 2)) {
    groups.classList.remove('d-none');
  }
  else {
    groups.classList.add('d-none');
  }
});