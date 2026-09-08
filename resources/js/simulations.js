const root = document.querySelector('[data-simulation-root]');

if (root) {
    const stateElement = document.getElementById(root.dataset.stateElement);
    let state = JSON.parse(stateElement.textContent);
    let submissionState = {};

    const elements = {
        score: root.querySelector('[data-simulation-score]'),
        success: root.querySelector('[data-simulation-success]'),
        progress: root.querySelector('[data-simulation-progress]'),
        progressBar: root.querySelector('[data-simulation-progress-bar]'),
        attempts: root.querySelector('[data-simulation-attempts]'),
        difficulty: root.querySelector('[data-simulation-difficulty]'),
        context: root.querySelector('[data-simulation-context]'),
        target: root.querySelector('[data-simulation-target]'),
        prompt: root.querySelector('[data-simulation-prompt]'),
        feedback: root.querySelector('[data-simulation-feedback]'),
        workspace: root.querySelector('[data-simulation-workspace]'),
        submit: root.querySelector('[data-simulation-submit]'),
        reset: root.querySelector('[data-simulation-reset]'),
        loading: root.querySelector('[data-simulation-loading]'),
    };

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    function button(label, className, onClick) {
        const control = document.createElement('button');
        control.type = 'button';
        control.className = className;
        control.textContent = label;
        control.addEventListener('click', onClick);

        return control;
    }

    function marathiNumber(value) {
        return String(value).replace(/[0-9]/g, (digit) => '०१२३४५६७८९'[Number(digit)]);
    }

    function renderState(nextState) {
        state = nextState;

        if (state.completed) {
            window.location.assign(root.dataset.resultUrl);
            return;
        }

        const challenge = state.challenge;
        const completed = state.stats.completed_count;
        const total = state.stats.challenge_count;
        const progressPercentage = total === 0 ? 0 : Math.round((completed / total) * 100);
        const interaction = challenge.interaction || {};

        elements.score.textContent = state.stats.score;
        elements.success.textContent = state.stats.successful_count;
        elements.progress.textContent = `${completed} / ${total}`;
        elements.progressBar.style.width = `${progressPercentage}%`;
        elements.progressBar.parentElement.setAttribute('aria-valuenow', progressPercentage);
        elements.attempts.textContent = `${challenge.attempts_used} / ${challenge.maximum_attempts}`;
        elements.difficulty.textContent = `पातळी ${marathiNumber(state.session.difficulty)}`;
        elements.prompt.textContent = challenge.prompt_marathi;
        elements.context.textContent = interaction.context_marathi || '';
        elements.context.hidden = !interaction.context_marathi;
        elements.target.textContent = interaction.visual || interaction.equation || interaction.target || '';
        elements.target.hidden = !elements.target.textContent;
        elements.feedback.hidden = true;
        renderInteraction(interaction);
    }

    function renderInteraction(interaction) {
        elements.workspace.replaceChildren();

        switch (interaction.type) {
            case 'number_line':
            case 'measurement':
                renderRange(interaction);
                break;
            case 'counter':
            case 'sharing':
            case 'fraction':
                renderCounter(interaction);
                break;
            case 'money':
                renderMoney(interaction);
                break;
            case 'place_value':
                renderPlaceValue(interaction);
                break;
            case 'array':
                renderArray(interaction);
                break;
            case 'clock':
                renderClock(interaction);
                break;
            case 'token_builder':
                renderTokenBuilder(interaction);
                break;
            default:
                elements.workspace.textContent = 'ही कृती सध्या उपलब्ध नाही.';
        }
    }

    function renderRange(interaction) {
        let value = Number(interaction.initial_value || 0);
        const output = document.createElement('strong');
        output.className = 'simulation-live-value';
        const range = document.createElement('input');
        range.type = 'range';
        range.className = 'form-range simulation-range';
        range.min = interaction.minimum;
        range.max = interaction.maximum;
        range.step = interaction.step || 1;
        range.value = value;
        range.setAttribute('aria-label', 'मूल्य निवडा');
        const track = document.createElement('div');
        track.className = interaction.type === 'number_line' ? 'simulation-number-line' : 'simulation-ruler';

        function update() {
            value = Number(range.value);
            submissionState = { value };
            output.textContent = `${marathiNumber(value)}${interaction.unit ? ` ${interaction.unit}` : ''}`;
            track.style.setProperty('--simulation-position', `${((value - range.min) / (range.max - range.min)) * 100}%`);
        }

        range.addEventListener('input', update);
        elements.workspace.append(output, track, range);
        update();
    }

    function renderCounter(interaction) {
        let value = Number(interaction.initial_value || 0);
        const controls = document.createElement('div');
        controls.className = 'simulation-counter-controls';
        const output = document.createElement('strong');
        output.className = 'simulation-live-value';
        const objects = document.createElement('div');
        objects.className = 'simulation-object-field';

        function update() {
            submissionState = { value };
            output.textContent = marathiNumber(value);
            objects.replaceChildren();

            if (interaction.type === 'sharing') {
                for (let group = 0; group < interaction.groups; group += 1) {
                    const groupElement = document.createElement('div');
                    groupElement.className = 'simulation-share-group';
                    groupElement.textContent = (interaction.object || '●').repeat(value);
                    objects.append(groupElement);
                }
            } else {
                const total = interaction.type === 'fraction' ? interaction.maximum : value;

                for (let index = 0; index < total; index += 1) {
                    const object = document.createElement('span');
                    object.className = 'simulation-object';
                    object.textContent = interaction.type === 'fraction'
                        ? (index < value ? '🍕' : '◯')
                        : (interaction.object || '●');
                    objects.append(object);
                }
            }
        }

        controls.append(
            button('−', 'btn btn-outline-danger simulation-step-button', () => {
                value = Math.max(Number(interaction.minimum), value - 1);
                update();
            }),
            output,
            button('+', 'btn btn-outline-success simulation-step-button', () => {
                value = Math.min(Number(interaction.maximum), value + 1);
                update();
            }),
        );
        elements.workspace.append(controls, objects);
        update();
    }

    function renderMoney(interaction) {
        let value = 0;
        const output = document.createElement('strong');
        output.className = 'simulation-live-value';
        const denominations = document.createElement('div');
        denominations.className = 'simulation-money-field';

        function update() {
            submissionState = { value };
            output.textContent = `₹${marathiNumber(value)}`;
        }

        interaction.denominations.forEach((denomination) => {
            denominations.append(button(
                `+ ₹${marathiNumber(denomination)}`,
                'btn btn-warning simulation-money-button',
                () => {
                    value = Math.min(Number(interaction.maximum), value + Number(denomination));
                    update();
                },
            ));
        });
        denominations.append(button('रक्कम शून्य करा', 'btn btn-outline-secondary', () => {
            value = 0;
            update();
        }));
        elements.workspace.append(output, denominations);
        update();
    }

    function renderPlaceValue(interaction) {
        const values = { hundreds: 0, tens: 0, ones: 0 };
        const field = document.createElement('div');
        field.className = 'simulation-place-value';
        const total = document.createElement('strong');
        total.className = 'simulation-live-value';
        const labels = {
            hundreds: 'शेकडा',
            tens: 'दशक',
            ones: 'एकक',
        };

        function update() {
            submissionState = { ...values };
            total.textContent = marathiNumber((values.hundreds * 100) + (values.tens * 10) + values.ones);
            field.querySelectorAll('[data-place-value]').forEach((output) => {
                output.textContent = marathiNumber(values[output.dataset.placeValue]);
            });
        }

        Object.keys(values).forEach((key) => {
            const column = document.createElement('div');
            column.className = 'simulation-place-column';
            const label = document.createElement('span');
            label.textContent = labels[key];
            const output = document.createElement('strong');
            output.dataset.placeValue = key;
            column.append(
                label,
                button('+', 'btn btn-success', () => {
                    values[key] = Math.min(interaction.maximum_digit, values[key] + 1);
                    update();
                }),
                output,
                button('−', 'btn btn-outline-danger', () => {
                    values[key] = Math.max(0, values[key] - 1);
                    update();
                }),
            );
            field.append(column);
        });
        elements.workspace.append(total, field);
        update();
    }

    function renderArray(interaction) {
        const values = { rows: 1, columns: 1 };
        const controls = document.createElement('div');
        controls.className = 'simulation-array-controls';
        const grid = document.createElement('div');
        grid.className = 'simulation-array-grid';
        const output = document.createElement('strong');
        output.className = 'simulation-live-value';

        function dimensionControl(key, label) {
            const group = document.createElement('div');
            const text = document.createElement('span');
            const value = document.createElement('strong');

            function updateLabel() {
                value.textContent = marathiNumber(values[key]);
            }

            text.textContent = label;
            group.append(
                text,
                button('−', 'btn btn-outline-secondary', () => {
                    values[key] = Math.max(1, values[key] - 1);
                    update();
                    updateLabel();
                }),
                value,
                button('+', 'btn btn-outline-success', () => {
                    values[key] = Math.min(interaction.maximum, values[key] + 1);
                    update();
                    updateLabel();
                }),
            );
            updateLabel();

            return group;
        }

        function update() {
            submissionState = { ...values };
            output.textContent = `${marathiNumber(values.rows)} × ${marathiNumber(values.columns)} = ${marathiNumber(values.rows * values.columns)}`;
            grid.replaceChildren();
            grid.style.setProperty('--simulation-columns', values.columns);

            for (let index = 0; index < values.rows * values.columns; index += 1) {
                const dot = document.createElement('span');
                dot.textContent = '●';
                grid.append(dot);
            }
        }

        controls.append(dimensionControl('rows', 'ओळी'), dimensionControl('columns', 'स्तंभ'));
        elements.workspace.append(output, controls, grid);
        update();
    }

    function renderClock(interaction) {
        const values = { hour: 12, minute: 0 };
        const clock = document.createElement('div');
        clock.className = 'simulation-clock';
        const output = document.createElement('strong');
        output.className = 'simulation-live-value';
        const controls = document.createElement('div');
        controls.className = 'simulation-clock-controls';

        function update() {
            submissionState = { ...values };
            output.textContent = `${marathiNumber(values.hour)}:${marathiNumber(String(values.minute).padStart(2, '0'))}`;
            clock.style.setProperty('--hour-angle', `${((values.hour % 12) * 30) + (values.minute * 0.5)}deg`);
            clock.style.setProperty('--minute-angle', `${values.minute * 6}deg`);
        }

        controls.append(
            button('तास −', 'btn btn-outline-secondary', () => {
                values.hour = values.hour === 1 ? 12 : values.hour - 1;
                update();
            }),
            button('तास +', 'btn btn-outline-success', () => {
                values.hour = values.hour === 12 ? 1 : values.hour + 1;
                update();
            }),
            button('मिनिट −', 'btn btn-outline-secondary', () => {
                values.minute = (values.minute - interaction.minute_step + 60) % 60;
                update();
            }),
            button('मिनिट +', 'btn btn-outline-success', () => {
                values.minute = (values.minute + interaction.minute_step) % 60;
                update();
            }),
        );
        elements.workspace.append(output, clock, controls);
        update();
    }

    function renderTokenBuilder(interaction) {
        const selectedTokens = [];
        const palette = document.createElement('div');
        palette.className = 'simulation-token-palette';
        const buildArea = document.createElement('div');
        buildArea.className = 'simulation-build-area';
        buildArea.tabIndex = 0;
        buildArea.setAttribute('aria-label', 'उत्तर तयार करण्याचे क्षेत्र');

        function addToken(value, label) {
            selectedTokens.push(value);
            const token = button(label, 'btn btn-primary simulation-built-token', () => {
                const tokenIndex = Array.from(buildArea.children).indexOf(token);
                selectedTokens.splice(tokenIndex, 1);
                token.remove();
                submissionState = { tokens: [...selectedTokens] };
            });
            buildArea.append(token);
            submissionState = { tokens: [...selectedTokens] };
        }

        interaction.tokens.forEach((token) => {
            const tokenButton = button(token.label, 'btn btn-outline-primary simulation-token', () => {
                addToken(token.value, token.label);
            });
            tokenButton.draggable = true;
            tokenButton.addEventListener('dragstart', (event) => {
                event.dataTransfer.setData('text/plain', JSON.stringify({
                    value: token.value,
                    label: token.label,
                }));
            });
            palette.append(tokenButton);
        });
        buildArea.addEventListener('dragover', (event) => event.preventDefault());
        buildArea.addEventListener('drop', (event) => {
            event.preventDefault();
            const token = JSON.parse(event.dataTransfer.getData('text/plain'));
            addToken(token.value, token.label);
        });
        submissionState = { tokens: [] };
        elements.workspace.append(palette, buildArea);
    }

    function showFeedback(feedback) {
        elements.feedback.className = `simulation-feedback simulation-feedback-${feedback.tone}`;
        elements.feedback.replaceChildren();
        const title = document.createElement('strong');
        title.textContent = feedback.title;
        const message = document.createElement('span');
        message.textContent = feedback.message;
        elements.feedback.append(title, message);
        elements.feedback.hidden = false;
    }

    async function submit() {
        if (!state.challenge) {
            return;
        }

        elements.submit.disabled = true;
        elements.reset.disabled = true;
        elements.loading.hidden = false;

        try {
            const response = await fetch(
                root.dataset.submitUrl.replace('__CHALLENGE__', state.challenge.id),
                {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ state: submissionState }),
                },
            );
            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.message || 'कृती तपासता आली नाही.');
            }

            renderState(payload);

            if (!payload.completed && payload.feedback) {
                showFeedback(payload.feedback);
            }
        } catch (error) {
            showFeedback({
                title: 'पुन्हा प्रयत्न करा',
                message: error.message,
                tone: 'encouragement',
            });
        } finally {
            elements.submit.disabled = false;
            elements.reset.disabled = false;
            elements.loading.hidden = true;
        }
    }

    elements.submit.addEventListener('click', submit);
    elements.reset.addEventListener('click', () => renderInteraction(state.challenge.interaction || {}));
    renderState(state);
}
