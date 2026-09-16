const root = document.querySelector('[data-game-root]');

if (root) {
    const stateElement = document.getElementById(root.dataset.stateElement);
    let state = JSON.parse(stateElement.textContent);
    let soundEnabled = true;
    let submitting = false;
    let timerId;
    let timerDeadline = state.session.remaining_seconds === null
        ? null
        : Date.now() + (state.session.remaining_seconds * 1000);

    const elements = {
        choices: root.querySelector('[data-game-choices]'),
        context: root.querySelector('[data-game-context]'),
        feedback: root.querySelector('[data-game-feedback]'),
        instructions: root.querySelector('[data-game-instructions]'),
        level: root.querySelector('[data-game-level]'),
        lives: root.querySelector('[data-game-lives]'),
        loading: root.querySelector('[data-game-loading]'),
        listen: root.querySelector('[data-game-listen]'),
        progress: root.querySelector('[data-game-progress]'),
        progressBar: root.querySelector('[data-game-progress-bar]'),
        progressTrack: root.querySelector('.game-progress-track'),
        prompt: root.querySelector('[data-game-prompt]'),
        score: root.querySelector('[data-game-score]'),
        sound: root.querySelector('[data-game-sound]'),
        timer: root.querySelector('[data-game-timer]'),
        visual: root.querySelector('[data-game-visual]'),
        actionHint: root.querySelector('[data-game-action-hint]'),
    };

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const instructions = {
        AKSHAR_PAKDA: 'सांगितलेले अक्षर पटकन पकडा.',
        NUMBER_CATCH: 'सांगितलेला अंक पटकन पकडा.',
        NUMBER_TRAIN: 'रिकाम्या डब्यात येणारा योग्य अंक निवडा.',
        GREATER_OR_SMALLER: 'सूचनेप्रमाणे मोठा किंवा लहान अंक निवडा.',
        PLACE_VALUE_HOUSE: 'घरातील दाखवलेल्या अंकाची स्थानिक किंमत ओळखा.',
        NUMBER_LINE_JUMP: 'संख्यारेषेवर उड्या मोजून योग्य जागा निवडा.',
        ADDITION_ADVENTURE: 'दोन्ही गट एकत्र मोजून बेरीज पूर्ण करा.',
        SUBTRACTION_ADVENTURE: 'कमी झालेल्या वस्तू वजा करून उत्तर शोधा.',
        FRACTION_PIZZA: 'रंगवलेल्या पिझ्झाच्या भागांचा अपूर्णांक निवडा.',
        SHOPPING_GAME: 'वस्तूंची संख्या आणि किंमत वापरून बिल मोजा.',
        MULTIPLICATION_SPACE_MISSION: 'ताऱ्यांच्या ओळी आणि स्तंभ मोजून गुणाकार करा.',
        DIVISION_SHARING_GAME: 'वस्तू समान गटांत वाटून प्रत्येक गटातील संख्या शोधा.',
        MATRA_BALLOONS: 'शब्द पूर्ण करणाऱ्या मात्रेचा फुगा फोडा.',
        BUILD_THE_WORD: 'अक्षरफलक योग्य क्रमाने स्पर्श करून शब्द तयार करा.',
        PICTURE_WORD_MATCH: 'चित्राशी जुळणारा शब्द निवडा.',
        WORD_TRAIN: 'त्याच गटातील शब्दाचा डबा गाडीला जोडा.',
        SENTENCE_MATCH: 'चित्राचा अर्थ सांगणारे योग्य वाक्य निवडा.',
        LISTEN_AND_SELECT: 'ऐका बटण दाबा आणि ऐकलेला शब्द निवडा.',
        FIND_CORRECT_WORD: 'अचूक लिहिलेला मराठी शब्द शोधा.',
        WORD_ORDER: 'शब्दफलक योग्य क्रमाने लावून वाक्य तयार करा.',
        READING_CHALLENGE: 'उतारा काळजीपूर्वक वाचा आणि प्रश्नाचे उत्तर द्या.',
        PATTERN_CODE_BREAKER: 'नियम शोधा आणि पुढील संख्या किंवा आकार निवडा.',
        DECIMAL_MARKET: 'दशांश स्थानांची तुलना करून व्यवहार पूर्ण करा.',
        FACTOR_MULTIPLE_LAB: 'विभाजक, पटी, मसावी आणि लसावी शोधा.',
        INTEGER_ELEVATOR: 'शून्याच्या वर-खाली जाऊन पूर्णांक सोडवा.',
        RATIO_RECIPE: 'प्रमाण कायम ठेवून योग्य गुणोत्तर निवडा.',
        PERCENTAGE_TARGET: 'अपूर्णांक आणि दशांशांचे शेकडेवारीत रूपांतर करा.',
        ALGEBRA_BALANCE: 'तराजू समतोल ठेवणारी अज्ञात संख्या शोधा.',
        ANGLE_DETECTIVE: 'कोनाचे माप आणि प्रकार काळजीपूर्वक ओळखा.',
        PERIMETER_AREA_BUILDER: 'मापांवरून परिमिती किंवा क्षेत्रफळ काढा.',
        DATA_GRAPH_CHALLENGE: 'तक्ता किंवा आलेख वाचून उत्तर शोधा.',
        CLOCK_CALENDAR_QUEST: 'वेळ, कालावधी आणि दिनदर्शिका वापरा.',
        SYNONYM_PAIRS: 'समान अर्थ असलेला शब्द निवडा.',
        ANTONYM_PAIRS: 'विरुद्ध अर्थ असलेला शब्द निवडा.',
        GENDER_NUMBER_SORT: 'शब्दाचे योग्य लिंग किंवा वचन रूप निवडा.',
        WORD_CLASS_DETECTIVE: 'वाक्यातील शब्दाची जात ओळखा.',
        TENSE_TRAVEL: 'वाक्याचा काळ ओळखा किंवा योग्य काळात बदला.',
        IDIOM_CONTEXT: 'संदर्भावरून वाक्प्रचार किंवा म्हणीचा अर्थ शोधा.',
        PUNCTUATION_RESCUE: 'वाक्य स्पष्ट करणारे विरामचिन्ह निवडा.',
        POETRY_EXPLORER: 'कविता वाचा आणि तिचा अर्थ शोधा.',
    };

    function playTone(isCorrect) {
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;

        if (!soundEnabled || !AudioContextClass) {
            return;
        }

        const context = new AudioContextClass();
        const oscillator = context.createOscillator();
        const gain = context.createGain();

        oscillator.type = 'sine';
        oscillator.frequency.value = isCorrect ? 660 : 220;
        gain.gain.setValueAtTime(0.12, context.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, context.currentTime + 0.18);
        oscillator.connect(gain);
        gain.connect(context.destination);
        oscillator.start();
        oscillator.stop(context.currentTime + 0.18);
        oscillator.addEventListener('ended', () => context.close());
    }

    function speak(text) {
        if (!soundEnabled || !text || !window.speechSynthesis) {
            return;
        }

        window.speechSynthesis.cancel();
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'mr-IN';
        utterance.rate = 0.82;
        window.speechSynthesis.speak(utterance);
    }

    function remainingSeconds() {
        if (timerDeadline === null) {
            return null;
        }

        return Math.max(0, Math.ceil((timerDeadline - Date.now()) / 1000));
    }

    async function finishGame() {
        if (submitting) {
            return;
        }

        submitting = true;
        elements.loading.hidden = false;

        try {
            const response = await fetch(root.dataset.finishUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({}),
            });

            if (!response.ok) {
                throw new Error('Game could not be completed.');
            }

            state = await response.json();
            window.location.assign(root.dataset.resultUrl);
        } catch {
            showError();
        } finally {
            submitting = false;
            elements.loading.hidden = true;
        }
    }

    function updateTimer() {
        const seconds = remainingSeconds();

        if (seconds === null) {
            elements.timer.textContent = '∞';
            return;
        }

        elements.timer.textContent = `${seconds}s`;

        if (seconds === 0) {
            window.clearInterval(timerId);
            finishGame();
        }
    }

    function showFeedback(feedback) {
        if (!feedback) {
            elements.feedback.hidden = true;
            return;
        }

        elements.feedback.className = `game-feedback game-feedback-${feedback.tone}`;
        elements.feedback.replaceChildren();
        const title = document.createElement('strong');
        const message = document.createElement('span');
        title.textContent = feedback.title;
        message.textContent = feedback.message;
        elements.feedback.append(title, message);
        elements.feedback.hidden = false;
        playTone(feedback.tone === 'success');
    }

    function showError() {
        elements.feedback.className = 'game-feedback game-feedback-error';
        elements.feedback.replaceChildren();
        const title = document.createElement('strong');
        const message = document.createElement('span');
        title.textContent = 'पुन्हा प्रयत्न करा';
        message.textContent = 'उत्तर जतन झाले नाही. इंटरनेट जोडणी तपासा.';
        elements.feedback.append(title, message);
        elements.feedback.hidden = false;
    }

    async function submitAnswer(questionId, value, button = null) {
        if (submitting) {
            return;
        }

        submitting = true;
        elements.choices.querySelectorAll('button').forEach((choice) => {
            choice.disabled = true;
        });
        button?.classList.add('is-selected');

        try {
            const answerUrl = root.dataset.answerUrl.replace('__QUESTION__', questionId);
            const response = await fetch(answerUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ answer: { value } }),
            });

            if (!response.ok) {
                throw new Error('Answer could not be saved.');
            }

            state = await response.json();
            timerDeadline = state.session.remaining_seconds === null
                ? null
                : Date.now() + (state.session.remaining_seconds * 1000);
            showFeedback(state.feedback);

            if (state.completed) {
                window.setTimeout(() => window.location.assign(root.dataset.resultUrl), 700);
                return;
            }

            window.setTimeout(() => {
                showFeedback(null);
                render();
                submitting = false;
            }, 650);
        } catch {
            showError();
            elements.choices.querySelectorAll('button').forEach((choice) => {
                choice.disabled = false;
            });
            submitting = false;
        }
    }

    function element(tagName, className, text = '') {
        const node = document.createElement(tagName);
        node.className = className;
        node.textContent = text;

        return node;
    }

    function choiceButton(question, choice, index, className = '') {
        const button = element(
            'button',
            `game-choice game-choice-${(index % 4) + 1} ${className}`.trim(),
            choice.label,
        );
        button.type = 'button';
        button.addEventListener('click', () => submitAnswer(question.id, choice.value, button));

        return button;
    }

    function renderStandardChoices(question, className = '') {
        elements.choices.innerHTML = '';
        elements.choices.className = `game-choice-field ${className}`.trim();

        question.choices.forEach((choice, index) => {
            elements.choices.appendChild(choiceButton(question, choice, index));
        });
    }

    function renderCatchChoices(question) {
        renderStandardChoices(question, 'game-catch-field');
        elements.choices.querySelectorAll('.game-choice').forEach((button, index) => {
            button.classList.add('game-catch-card');
            button.style.setProperty('--catch-delay', `${index * 90}ms`);
        });
    }

    function renderFractionChoices(question) {
        elements.choices.innerHTML = '';
        elements.choices.className = 'game-choice-field game-fraction-choices';

        question.choices.forEach((choice, index) => {
            const button = choiceButton(question, choice, index, 'game-fraction-choice');
            const [numerator, denominator] = choice.label.split('/').map(Number);
            const pizza = element('span', 'game-mini-pizza');
            pizza.style.setProperty('--fraction-turn', `${(numerator / denominator) * 360}deg`);
            button.replaceChildren(pizza, element('strong', '', choice.label));
            elements.choices.appendChild(button);
        });
    }

    function normalizeCandidate(value) {
        return value.replace(/[.।!?]/gu, '').replace(/\s+/gu, ' ').trim();
    }

    function renderTokenBuilder(question, mode) {
        const tokens = [...(question.presentation.tokens || [])];
        const selected = [];
        elements.choices.innerHTML = '';
        elements.choices.className = 'game-token-builder';
        elements.actionHint.textContent = mode === 'word'
            ? 'अक्षरे योग्य क्रमाने निवडा.'
            : 'शब्द योग्य क्रमाने निवडा.';

        const answerTray = element('div', 'game-token-answer');
        const tokenBank = element('div', 'game-token-bank');
        const controls = element('div', 'game-token-controls');
        const resetButton = element('button', 'btn btn-outline-secondary', 'पुन्हा मांडणी');
        const submitButton = element('button', 'btn btn-primary', 'उत्तर तपासा');
        resetButton.type = 'button';
        submitButton.type = 'button';
        submitButton.disabled = true;
        controls.append(resetButton, submitButton);
        elements.choices.append(answerTray, tokenBank, controls);

        const refresh = () => {
            answerTray.replaceChildren();
            tokenBank.replaceChildren();

            if (selected.length === 0) {
                answerTray.appendChild(element('span', 'game-token-placeholder', 'इथे तयार करा'));
            } else {
                selected.forEach((token) => {
                    const tokenButton = element('button', 'game-token is-selected', token.value);
                    tokenButton.type = 'button';
                    tokenButton.addEventListener('click', () => {
                        selected.splice(selected.indexOf(token), 1);
                        refresh();
                    });
                    answerTray.appendChild(tokenButton);
                });
            }

            tokens.filter((token) => !selected.includes(token)).forEach((token) => {
                const tokenButton = element('button', 'game-token', token.value);
                tokenButton.type = 'button';
                tokenButton.addEventListener('click', () => {
                    selected.push(token);
                    refresh();
                });
                tokenBank.appendChild(tokenButton);
            });

            const separator = mode === 'word' ? '' : ' ';
            const candidate = selected.map((token) => token.value).join(separator);
            const offeredChoice = question.choices.find(
                (choice) => normalizeCandidate(choice.value) === normalizeCandidate(candidate),
            );
            submitButton.disabled = offeredChoice === undefined || selected.length !== tokens.length;
            submitButton.dataset.answerValue = offeredChoice?.value || '';
        };

        const tokenObjects = tokens.map((value, index) => ({ id: index, value }));
        tokens.splice(0, tokens.length, ...tokenObjects);
        resetButton.addEventListener('click', () => {
            selected.splice(0, selected.length);
            refresh();
        });
        submitButton.addEventListener('click', () => {
            if (submitButton.dataset.answerValue) {
                submitAnswer(question.id, submitButton.dataset.answerValue, submitButton);
            }
        });
        refresh();
    }

    function appendDots(parent, count, className = 'game-counter-dot') {
        for (let index = 0; index < count; index += 1) {
            parent.appendChild(element('span', className));
        }
    }

    function renderVisual(question) {
        const presentation = question.presentation || {};
        const interaction = presentation.interaction;
        elements.visual.replaceChildren();
        elements.visual.className = `game-visual game-visual-${interaction || 'default'}`;
        elements.visual.hidden = false;

        if (interaction === 'number_train') {
            const train = element('div', 'game-number-train');
            train.appendChild(element('span', 'game-train-engine', '🚂'));
            presentation.sequence.forEach((value) => {
                train.appendChild(element(
                    'span',
                    `game-train-car ${value === '__' ? 'is-empty' : ''}`,
                    value === '__' ? '?' : value,
                ));
            });
            elements.visual.appendChild(train);
            return;
        }

        if (interaction === 'number_comparison') {
            const scale = element('div', 'game-comparison-scale');
            scale.append(
                element('span', 'game-comparison-number', presentation.numbers[0]),
                element('span', 'game-scale-icon', '⚖️'),
                element('span', 'game-comparison-number', presentation.numbers[1]),
            );
            elements.visual.appendChild(scale);
            return;
        }

        if (interaction === 'place_value_house') {
            const house = element('div', 'game-place-house');
            const digits = [...presentation.number];
            const labels = digits.length === 3 ? ['शेकडा', 'दशक', 'एकक'] : ['दशक', 'एकक'];
            house.style.setProperty('--place-columns', digits.length);
            house.appendChild(element('div', 'game-place-roof', presentation.number));
            digits.forEach((digit, index) => {
                const room = element(
                    'div',
                    `game-place-room ${digit === presentation.target_digit ? 'is-target' : ''}`,
                );
                room.append(element('strong', '', digit), element('span', '', labels[index]));
                house.appendChild(room);
            });
            elements.visual.appendChild(house);
            return;
        }

        if (interaction === 'number_line') {
            const line = element('div', 'game-number-line');
            const end = presentation.start + presentation.jump + 3;
            for (let value = Math.max(0, presentation.start - 2); value <= end; value += 1) {
                const marker = element(
                    'span',
                    `game-number-marker ${value === presentation.start ? 'is-start' : ''}`,
                    value.toLocaleString('mr-IN'),
                );
                line.appendChild(marker);
            }
            const jump = element('div', 'game-jump-arrow', `+${presentation.jump.toLocaleString('mr-IN')} उड्या`);
            elements.visual.append(line, jump);
            return;
        }

        if (interaction === 'arithmetic_adventure') {
            const board = element('div', 'game-arithmetic-board');
            const [first, second] = presentation.operands;
            const firstGroup = element('div', 'game-counter-group');
            const secondGroup = element('div', 'game-counter-group');
            appendDots(firstGroup, first);
            appendDots(secondGroup, second, presentation.operator === '−' ? 'game-counter-dot is-removed' : 'game-counter-dot');
            board.append(firstGroup, element('strong', 'game-math-operator', presentation.operator), secondGroup);
            elements.visual.appendChild(board);
            return;
        }

        if (interaction === 'fraction_pizza') {
            const pizza = element('div', 'game-pizza');
            pizza.style.setProperty(
                '--fraction-turn',
                `${(presentation.numerator / presentation.denominator) * 360}deg`,
            );
            elements.visual.appendChild(pizza);
            return;
        }

        if (interaction === 'shopping') {
            const receipt = element('div', 'game-shop-receipt');
            receipt.append(
                element('div', 'game-shop-items', presentation.item_visual.repeat(presentation.quantity)),
                element('strong', '', `${presentation.quantity.toLocaleString('mr-IN')} × ₹${presentation.price.toLocaleString('mr-IN')}`),
                element('span', '', `${presentation.item_name} · एकूण बिल किती?`),
            );
            elements.visual.appendChild(receipt);
            return;
        }

        if (interaction === 'multiplication_array') {
            const array = element('div', 'game-star-array');
            array.style.setProperty('--array-columns', Math.min(presentation.columns, 12));
            appendDots(array, presentation.rows * presentation.columns, 'game-array-star');
            elements.visual.appendChild(array);
            return;
        }

        if (interaction === 'division_sharing') {
            const sharing = element('div', 'game-sharing-board');
            const objects = element('div', 'game-sharing-pile');
            const baskets = element('div', 'game-sharing-baskets');
            appendDots(objects, presentation.objects, 'game-sharing-object');
            for (let group = 0; group < presentation.groups; group += 1) {
                const basket = element('div', 'game-sharing-basket');
                basket.appendChild(element('span', '', `${(group + 1).toLocaleString('mr-IN')}`));
                baskets.appendChild(basket);
            }
            sharing.append(objects, baskets);
            elements.visual.appendChild(sharing);
            return;
        }

        if (interaction === 'word_train') {
            const train = element('div', 'game-number-train game-word-train');
            train.appendChild(element('span', 'game-train-engine', '🚂'));
            question.prompt_marathi.split('→').forEach((word) => {
                train.appendChild(element('span', 'game-train-car', word.trim()));
            });
            elements.visual.appendChild(train);
            return;
        }

        if (presentation.visual) {
            elements.visual.textContent = presentation.visual;
            return;
        }

        elements.visual.hidden = true;
    }

    function renderChoices(question) {
        const interaction = question.presentation?.interaction;
        elements.actionHint.textContent = 'योग्य पर्यायाला स्पर्श करा.';

        if (interaction === 'word_builder') {
            renderTokenBuilder(question, 'word');
            return;
        }

        if (interaction === 'word_order') {
            renderTokenBuilder(question, 'sentence');
            return;
        }

        if (interaction === 'catch') {
            renderCatchChoices(question);
            return;
        }

        if (interaction === 'matra_balloons') {
            renderStandardChoices(question, 'game-balloon-field');
            return;
        }

        if (interaction === 'fraction_pizza') {
            renderFractionChoices(question);
            return;
        }

        const compactInteractions = [
            'picture_match',
            'sentence_match',
            'reading_challenge',
            'listen_and_select',
            'correct_word',
            'word_train',
            'pattern_lab',
            'decimal_lab',
            'factor_lab',
            'integer_lab',
            'ratio_lab',
            'percentage_lab',
            'algebra_lab',
            'angle_lab',
            'measurement_lab',
            'data_lab',
            'time_lab',
            'synonym_pairs',
            'antonym_pairs',
            'grammar_sort',
            'word_class',
            'tense_timeline',
            'context_clue',
            'punctuation',
            'poetry_reading',
        ];
        renderStandardChoices(
            question,
            compactInteractions.includes(interaction) ? 'game-choice-field-words' : '',
        );
    }

    function render() {
        const { stats, question, session } = state;
        const percent = stats.question_count === 0
            ? 0
            : Math.round((stats.answered_count / stats.question_count) * 100);

        elements.score.textContent = stats.score;
        elements.lives.textContent = `${'♥ '.repeat(stats.lives_remaining)}${'♡ '.repeat(Math.max(0, stats.lives_total - stats.lives_remaining))}`.trim();
        elements.progress.textContent = `${stats.answered_count} / ${stats.question_count}`;
        elements.progressBar.style.width = `${percent}%`;
        elements.progressTrack.setAttribute('aria-valuenow', percent);
        elements.level.textContent = `पातळी ${session.level} · ${session.level_name}`;
        elements.instructions.textContent = instructions[state.game.code] || 'प्रश्न सोडवा आणि पुढची पातळी गाठा.';
        root.dataset.interaction = question?.presentation?.interaction || state.game.engine_key;

        if (question) {
            const presentation = question.presentation || {};
            elements.prompt.textContent = question.prompt_marathi;
            elements.context.textContent = presentation.context_marathi || '';
            elements.context.hidden = !presentation.context_marathi;
            elements.listen.hidden = !presentation.audio_text;
            elements.listen.dataset.audioText = presentation.audio_text || '';
            renderVisual(question);
            renderChoices(question);
        }

        updateTimer();
    }

    elements.sound.addEventListener('click', () => {
        soundEnabled = !soundEnabled;
        elements.sound.setAttribute('aria-pressed', soundEnabled ? 'true' : 'false');
        elements.sound.querySelector('i').className = `bi bi-volume-${soundEnabled ? 'up' : 'mute'}`;
        elements.sound.querySelector('span').textContent = soundEnabled ? 'आवाज सुरू' : 'आवाज बंद';
    });
    elements.listen.addEventListener('click', () => speak(elements.listen.dataset.audioText));

    render();
    timerId = window.setInterval(updateTimer, 1000);
}
