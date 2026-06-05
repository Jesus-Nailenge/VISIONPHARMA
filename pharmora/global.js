// Função para abrir o modal de confirmação
function confirmarAcao(titulo, mensagem, corBotao = '#e74c3c') {
    return new Promise((resolve) => {
        const modal = document.getElementById('modal-confirm');
        const titleEl = document.getElementById('confirm-title');
        const msgEl = document.getElementById('confirm-msg');
        const btnExec = document.getElementById('btn-confirm-execute');

        titleEl.innerText = titulo;
        msgEl.innerText = mensagem;
        btnExec.style.backgroundColor = corBotao;
        
        modal.classList.add('active');

        // Define o que acontece no clique do botão confirmar
        btnExec.onclick = () => {
            modal.classList.remove('active');
            resolve(true);
        };

        // Se clicar fora do modal também cancela
        modal.onclick = (e) => {
            if(e.target === modal) {
                modal.classList.remove('active');
                resolve(false);
            }
        };
    });
}

function closeConfirmModal() {
    document.getElementById('modal-confirm').classList.remove('active');
}