/**
 * Máscaras e utilitários para formulários
 * Supersites - Sistema de gestão
 *
 * Requer: IMask library (https://unpkg.com/imask)
 */

const InputMasks = {
    /**
     * Máscara para CPF
     * @param {HTMLElement} input - Elemento input
     * @returns {IMask} Instância do IMask
     */
    cpf: function(input) {
        if (!input || typeof IMask === 'undefined') return null;
        return IMask(input, {
            mask: '000.000.000-00'
        });
    },

    /**
     * Máscara para CNPJ
     * @param {HTMLElement} input - Elemento input
     * @returns {IMask} Instância do IMask
     */
    cnpj: function(input) {
        if (!input || typeof IMask === 'undefined') return null;
        return IMask(input, {
            mask: '00.000.000/0000-00'
        });
    },

    /**
     * Máscara para CPF/CNPJ dinâmico (alterna baseado no tamanho)
     * @param {HTMLElement} input - Elemento input
     * @returns {IMask} Instância do IMask
     */
    cpfCnpj: function(input) {
        if (!input) return null;

        // Fallback para vanilla JS se IMask não disponível
        if (typeof IMask === 'undefined') {
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');

                if (value.length <= 11) {
                    // CPF: 000.000.000-00
                    value = value.replace(/(\d{3})(\d)/, '$1.$2');
                    value = value.replace(/(\d{3})(\d)/, '$1.$2');
                    value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                } else {
                    // CNPJ: 00.000.000/0000-00
                    value = value.replace(/^(\d{2})(\d)/, '$1.$2');
                    value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                    value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
                    value = value.replace(/(\d{4})(\d)/, '$1-$2');
                }

                e.target.value = value;
            });
            return null;
        }

        return IMask(input, {
            mask: [
                {mask: '000.000.000-00', lazy: false},
                {mask: '00.000.000/0000-00', lazy: false}
            ]
        });
    },

    /**
     * Máscara para RG (Documento de identidade brasileiro)
     * @param {HTMLElement} input - Elemento input
     * @returns {IMask} Instância do IMask
     */
    rg: function(input) {
        if (!input || typeof IMask === 'undefined') return null;
        return IMask(input, {
            mask: '00.000.000-0'
        });
    },

    /**
     * Máscara para telefone/celular com seletor de país
     * @param {HTMLElement} input - Elemento input
     * @returns {object} Instância do intlTelInput ou IMask
     */
    phone: function(input) {
        if (!input) return null;

        // Usar intl-tel-input se disponível (com bandeirinhas de países)
        if (typeof window.intlTelInput !== 'undefined') {
            const iti = window.intlTelInput(input, {
                initialCountry: "br",
                preferredCountries: ["br", "us", "pt", "es", "ar"],
                separateDialCode: true,
                autoPlaceholder: "aggressive",
                formatOnDisplay: true,
                nationalMode: false,
                autoFormat: true,
                utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/utils.js"
            });

            // Aplicar formatação em tempo real
            input.addEventListener('input', function() {
                // A biblioteca já formata automaticamente quando utils.js está carregado
                // Apenas garantir que está formatando
                if (iti.isValidNumber && iti.isValidNumber()) {
                    const formattedNumber = iti.getNumber();
                }
            });

            // Salvar número completo com código do país no envio
            input.addEventListener('blur', function() {
                if (input.value.trim()) {
                    const fullNumber = iti.getNumber();
                    input.setAttribute('data-full-number', fullNumber);
                }
            });

            return iti;
        }

        // Fallback 1: IMask se disponível
        if (typeof IMask !== 'undefined') {
            return IMask(input, {
                mask: [
                    {mask: '(00) 0000-0000'},
                    {mask: '(00) 00000-0000'}
                ]
            });
        }

        // Fallback 2: Vanilla JS
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');

            if (value.length <= 10) {
                // Telefone fixo: (00) 0000-0000
                value = value.replace(/(\d{2})(\d)/, '($1) $2');
                value = value.replace(/(\d{4})(\d)/, '$1-$2');
            } else {
                // Celular: (00) 00000-0000
                value = value.replace(/(\d{2})(\d)/, '($1) $2');
                value = value.replace(/(\d{5})(\d)/, '$1-$2');
            }

            e.target.value = value;
        });
        return null;
    },

    /**
     * Máscara para CEP
     * @param {HTMLElement} input - Elemento input
     * @param {object} options - { autoFill: boolean, fieldIds: {...} }
     * @returns {IMask} Instância do IMask
     */
    cep: function(input, options = {}) {
        if (!input) return null;

        // Fallback para vanilla JS se IMask não disponível
        if (typeof IMask === 'undefined') {
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');

                if (value.length <= 8) {
                    value = value.replace(/^(\d{5})(\d)/, '$1-$2');
                }

                e.target.value = value;
            });

            // Configurar autopreenchimento se solicitado
            if (options.autoFill) {
                this._setupCepAutoFill(input, options.fieldIds || {});
            }

            return null;
        }

        const mask = IMask(input, {
            mask: '00000-000'
        });

        // Configurar autopreenchimento se solicitado
        if (options.autoFill) {
            this._setupCepAutoFill(input, options.fieldIds || {});
        }

        return mask;
    },

    /**
     * Máscara para valor monetário (R$)
     * @param {HTMLElement} input - Elemento input
     * @returns {IMask} Instância do IMask
     */
    money: function(input) {
        if (!input || typeof IMask === 'undefined') return null;
        return IMask(input, {
            mask: 'R$ num',
            blocks: {
                num: {
                    mask: Number,
                    scale: 2,
                    thousandsSeparator: '.',
                    padFractionalZeros: true,
                    radix: ',',
                    mapToRadix: ['.']
                }
            }
        });
    },

    /**
     * Máscara para data (DD/MM/AAAA)
     * @param {HTMLElement} input - Elemento input
     * @returns {IMask} Instância do IMask
     */
    date: function(input) {
        if (!input || typeof IMask === 'undefined') return null;
        return IMask(input, {
            mask: '00/00/0000'
        });
    },

    /**
     * Configurar autopreenchimento de CEP (uso interno)
     * @private
     */
    _setupCepAutoFill: function(input, fieldIds) {
        input.addEventListener('blur', async function() {
            const cep = this.value.replace(/\D/g, '');
            if (cep.length === 8) {
                await InputMasks.buscarCep(cep, {
                    success: (data) => {
                        if (fieldIds.street) {
                            const street = document.getElementById(fieldIds.street);
                            if (street) street.value = data.logradouro;
                        }
                        if (fieldIds.neighborhood) {
                            const neighborhood = document.getElementById(fieldIds.neighborhood);
                            if (neighborhood) neighborhood.value = data.bairro;
                        }
                        if (fieldIds.city) {
                            const city = document.getElementById(fieldIds.city);
                            if (city) city.value = data.localidade;
                        }
                        if (fieldIds.state) {
                            const state = document.getElementById(fieldIds.state);
                            if (state) state.value = data.uf;
                        }
                        if (fieldIds.number) {
                            const number = document.getElementById(fieldIds.number);
                            if (number) setTimeout(() => number.focus(), 100);
                        }
                    },
                    error: (message) => {
                        console.warn('Erro ao buscar CEP:', message);
                    }
                });
            }
        });
    },

    /**
     * Buscar endereço via CEP usando ViaCEP
     * @param {string} cep - CEP a ser buscado
     * @param {object} callbacks - { success: function(data), error: function(message) }
     */
    buscarCep: async function(cep, callbacks = {}) {
        cep = cep.replace(/\D/g, '');

        if (cep.length !== 8) {
            if (callbacks.error) {
                callbacks.error('CEP deve ter 8 dígitos');
            }
            return;
        }

        try {
            const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
            const data = await response.json();

            if (!data.erro) {
                if (callbacks.success) {
                    callbacks.success(data);
                }
            } else {
                if (callbacks.error) {
                    callbacks.error('CEP não encontrado');
                }
            }
        } catch (error) {
            if (callbacks.error) {
                callbacks.error('Erro ao buscar CEP. Tente novamente.');
            }
        }
    },

    /**
     * Aplicar todas as máscaras automaticamente baseado em classes CSS
     */
    autoApply: function() {
        // CPF
        document.querySelectorAll('.cpf-mask').forEach(el => this.cpf(el));

        // CNPJ
        document.querySelectorAll('.cnpj-mask').forEach(el => this.cnpj(el));

        // CPF/CNPJ dinâmico
        document.querySelectorAll('[id*="cpf"], [id*="cnpj"], [name*="cpf_cnpj"]').forEach(el => {
            if (!el.classList.contains('cpf-mask') && !el.classList.contains('cnpj-mask')) {
                this.cpfCnpj(el);
            }
        });

        // RG
        document.querySelectorAll('.rg-mask').forEach(el => this.rg(el));

        // Telefone
        document.querySelectorAll('.phone-mask, [id*="phone"], [id*="telefone"], [name*="phone"]').forEach(el => {
            this.phone(el);
        });

        // CEP
        document.querySelectorAll('.cep-mask, [id*="cep"], [name*="cep"]').forEach(el => {
            const addressTrigger = el.getAttribute('data-address-trigger');
            if (addressTrigger) {
                // Com autopreenchimento de endereço
                this.cep(el, {
                    autoFill: true,
                    fieldIds: {
                        street: `${addressTrigger}_street`,
                        neighborhood: `${addressTrigger}_neighborhood`,
                        city: `${addressTrigger}_city`,
                        state: `${addressTrigger}_state`,
                        number: `${addressTrigger}_number`
                    }
                });
            } else {
                this.cep(el);
            }
        });

        // Money
        document.querySelectorAll('.money-mask').forEach(el => this.money(el));

        // Date
        document.querySelectorAll('.date-mask').forEach(el => this.date(el));
    }
};

/**
 * Configuração automática para busca de CEP com feedback visual
 * @param {object} config - { cepInputId, streetInputId, neighborhoodInputId, cityInputId, stateInputId, numberInputId, loadingElementId }
 */
function setupCepAutocomplete(config) {
    const cepInput = document.getElementById(config.cepInputId);
    
    if (!cepInput) return;
    
    cepInput.addEventListener('blur', async function() {
        const cep = this.value.replace(/\D/g, '');
        
        if (cep.length !== 8) return;
        
        const loadingElement = config.loadingElementId ? document.getElementById(config.loadingElementId) : null;
        
        try {
            // Mostrar loading se existir
            if (loadingElement) loadingElement.classList.remove('hidden');
            cepInput.style.backgroundColor = '#f0f8ff'; // Azul claro
            
            await InputMasks.buscarCep(cep, {
                success: (data) => {
                    const fields = [
                        { id: config.streetInputId, value: data.logradouro },
                        { id: config.neighborhoodInputId, value: data.bairro },
                        { id: config.cityInputId, value: data.localidade },
                        { id: config.stateInputId, value: data.uf }
                    ];
                    
                    // Preencher campos com feedback visual
                    fields.forEach(field => {
                        const input = document.getElementById(field.id);
                        if (input && field.value) {
                            input.value = field.value;
                            input.style.backgroundColor = '#f0fff0'; // Verde claro
                        }
                    });
                    
                    // CEP encontrado - verde
                    cepInput.style.backgroundColor = '#f0fff0';
                    
                    // Focar no campo número se existir
                    if (config.numberInputId) {
                        const numberInput = document.getElementById(config.numberInputId);
                        if (numberInput) setTimeout(() => numberInput.focus(), 100);
                    }
                    
                    // Remover destaque após 2 segundos
                    setTimeout(() => {
                        [cepInput, ...fields.map(f => document.getElementById(f.id))]
                            .forEach(input => {
                                if (input) input.style.backgroundColor = '';
                            });
                    }, 2000);
                },
                error: (message) => {
                    // CEP não encontrado - vermelho claro
                    cepInput.style.backgroundColor = '#fff5f5';
                    cepInput.title = message;
                    
                    setTimeout(() => {
                        cepInput.style.backgroundColor = '';
                        cepInput.title = '';
                    }, 3000);
                }
            });
        } finally {
            // Esconder loading
            if (loadingElement) loadingElement.classList.add('hidden');
        }
    });
}

// Exportar para uso global
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { InputMasks, setupCepAutocomplete };
}