document.addEventListener('DOMContentLoaded', () => {
    // 1. Alternador de Tema (Dark Mode)
    const themeToggle = document.getElementById('theme-toggle');
    const body = document.body;

    // Função para aplicar o tema (light-mode ou dark-mode)
    function applyTheme(theme) {
        if (theme === 'dark-mode') {
            body.classList.remove('light-mode');
            body.classList.add('dark-mode');
            // Ícone de sol para indicar que o próximo clique ativará o modo claro
            themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
        } else {
            body.classList.remove('dark-mode');
            body.classList.add('light-mode');
            // Ícone de lua para indicar que o próximo clique ativará o modo escuro
            themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
        }
        // Salva a preferência do usuário no armazenamento local
        localStorage.setItem('theme', theme);
    }

    // Inicialização do Tema:
    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;

    if (savedTheme) {
        applyTheme(savedTheme);
    } else if (prefersDark) {
        // Aplica o tema escuro se for a preferência do sistema operacional do usuário
        applyTheme('dark-mode');
    } else {
        // Padrão para light-mode
        applyTheme('light-mode');
    }

    // Evento de clique para alternar o tema
    themeToggle.addEventListener('click', () => {
        if (body.classList.contains('light-mode')) {
            applyTheme('dark-mode');
        } else {
            applyTheme('light-mode');
        }
    });

    // 2. Contador de Conceitos (Simulação de Interatividade)
    const learnedConceptsElement = document.getElementById('learned-concepts');
    const maxConcepts = 42; // O número total de conceitos para o contador animar

    // Função para simular o aprendizado gradual com animação
    function updateConceptCounter() {
        if (!learnedConceptsElement) return; // Garante que o elemento existe

        const targetNumber = maxConcepts;
        const duration = 2000; // Duração da animação em milissegundos (2 segundos)
        let startTime;

        const animateCount = (timestamp) => {
            if (!startTime) startTime = timestamp;
            const progress = timestamp - startTime;
            // Garante que o progresso não exceda 1 (100%)
            const percentage = Math.min(progress / duration, 1);
            
            // Calcula o valor atual do contador, arredondado para baixo
            const currentValue = Math.floor(percentage * targetNumber);
            learnedConceptsElement.textContent = currentValue;

            if (percentage < 1) {
                // Continua a animação no próximo frame
                requestAnimationFrame(animateCount);
            } else {
                // Garante que o número final seja o alvo exato
                learnedConceptsElement.textContent = targetNumber;
            }
        };

        // Inicia a animação
        requestAnimationFrame(animateCount);
    }

    // Chama a animação do contador quando a página é carregada
    updateConceptCounter();
});