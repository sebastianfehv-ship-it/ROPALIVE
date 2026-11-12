(() => {
    let activeForm = null;
    let activeSubmitter = null;
    const dialog = document.createElement('dialog');
    dialog.className = 'confirmation-dialog';
    dialog.setAttribute('aria-labelledby', 'confirmation-title');
    dialog.innerHTML = `
        <div class="confirmation-dialog-content">
            <h2 id="confirmation-title"></h2>
            <p class="confirmation-message"></p>
            <div class="confirmation-actions">
                <button class="button button-light confirmation-cancel" type="button">Cancelar</button>
                <button class="button confirmation-accept" type="button">Sí, continuar</button>
            </div>
        </div>`;
    document.body.append(dialog);

    const title = dialog.querySelector('#confirmation-title');
    const message = dialog.querySelector('.confirmation-message');
    const cancelButton = dialog.querySelector('.confirmation-cancel');
    const acceptButton = dialog.querySelector('.confirmation-accept');

    const finish = (accepted) => {
        const form = activeForm;
        const submitter = activeSubmitter;
        activeForm = null;
        activeSubmitter = null;
        if (dialog.open) {
            dialog.close();
        }

        if (!accepted || !form) {
            return;
        }

        form.dataset.confirmed = 'true';
        if (submitter) {
            form.requestSubmit(submitter);
        } else {
            form.requestSubmit();
        }
    };

    cancelButton.addEventListener('click', () => finish(false));
    acceptButton.addEventListener('click', () => finish(true));
    dialog.addEventListener('close', () => {
        activeForm = null;
        activeSubmitter = null;
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.dataset.confirmMessage) {
            return;
        }

        if (form.dataset.confirmed === 'true') {
            delete form.dataset.confirmed;
            return;
        }

        event.preventDefault();
        activeForm = form;
        activeSubmitter = event.submitter;
        title.textContent = form.dataset.confirmTitle || 'Confirmar acción';
        message.textContent = form.dataset.confirmMessage;
        acceptButton.textContent = form.dataset.confirmLabel || 'Sí, continuar';
        acceptButton.className = form.dataset.confirmDanger === 'true'
            ? 'button button-danger confirmation-accept'
            : 'button confirmation-accept';

        if (typeof dialog.showModal === 'function') {
            dialog.showModal();
            cancelButton.focus();
            return;
        }

        finish(window.confirm(message.textContent));
    }, true);
})();
