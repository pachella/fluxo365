<?php
// Decodificar config
$config = !empty($field['config']) ? json_decode($field['config'], true) : [];
$showComplementary = !empty($config['show_complementary_fields']);
?>

<!-- Campo principal: Número do RG -->
<input type="text"
       name="<?= $showComplementary ? $fieldName . '[rg_number]' : $fieldName ?>"
       id="<?= $fieldName ?>"
       placeholder="00.000.000-0"
       <?= $field['required'] ? 'required' : '' ?>
       autocomplete="off"
       class="w-full px-4 py-3 text-lg border-b-2 border-gray-300 dark:border-zinc-600 bg-transparent focus:outline-none focus:border-current transition-colors rg-mask"
       style="border-color: <?= $customization['text_color'] ?>; opacity: 0.3;"
       maxlength="12"
       <?php if ($showComplementary): ?>
       onblur="showRgComplementaryFields('<?= $fieldName ?>')"
       <?php endif; ?>
       autofocus>

<style>
    #<?= $fieldName ?>:focus {
        opacity: 1 !important;
        border-color: <?= $customization['primary_color'] ?> !important;
    }
</style>

<?php if ($showComplementary): ?>
<!-- Campos complementares do RG (mostrados após preencher o RG) -->
<div id="<?= $fieldName ?>_complementary" class="mt-6 space-y-4" style="display: none;">
    <!-- Data de Nascimento -->
    <div>
        <label class="block text-sm font-medium mb-2" style="color: <?= $customization['text_color'] ?>; opacity: 0.8;">
            Data de Nascimento
        </label>
        <input type="text"
               name="<?= $fieldName ?>[birth_date]"
               id="<?= $fieldName ?>_birth_date"
               placeholder="DD/MM/AAAA"
               <?= $field['required'] ? 'required' : '' ?>
               class="w-full px-4 py-3 text-lg border-b-2 border-gray-300 dark:border-zinc-600 bg-transparent focus:outline-none focus:border-current transition-colors date-mask"
               style="border-color: <?= $customization['text_color'] ?>; opacity: 0.3;"
               maxlength="10">
        <style>
            #<?= $fieldName ?>_birth_date:focus {
                opacity: 1 !important;
                border-color: <?= $customization['primary_color'] ?> !important;
            }
        </style>
    </div>

    <!-- Naturalidade -->
    <div>
        <label class="block text-sm font-medium mb-2" style="color: <?= $customization['text_color'] ?>; opacity: 0.8;">
            Naturalidade
        </label>
        <select name="<?= $fieldName ?>[nationality]"
                id="<?= $fieldName ?>_nationality"
                <?= $field['required'] ? 'required' : '' ?>
                class="w-full px-4 py-3 text-lg border-b-2 border-gray-300 dark:border-zinc-600 bg-transparent focus:outline-none focus:border-current transition-colors"
                style="border-color: <?= $customization['text_color'] ?>; opacity: 0.3;">
            <option value="">Selecione...</option>
            <option value="Brasil">Brasil</option>
            <option disabled>──────────</option>
            <option value="Afeganistão">Afeganistão</option>
            <option value="África do Sul">África do Sul</option>
            <option value="Albânia">Albânia</option>
            <option value="Alemanha">Alemanha</option>
            <option value="Andorra">Andorra</option>
            <option value="Angola">Angola</option>
            <option value="Arábia Saudita">Arábia Saudita</option>
            <option value="Argélia">Argélia</option>
            <option value="Argentina">Argentina</option>
            <option value="Armênia">Armênia</option>
            <option value="Austrália">Austrália</option>
            <option value="Áustria">Áustria</option>
            <option value="Azerbaijão">Azerbaijão</option>
            <option value="Bahamas">Bahamas</option>
            <option value="Bangladesh">Bangladesh</option>
            <option value="Barbados">Barbados</option>
            <option value="Barém">Barém</option>
            <option value="Bélgica">Bélgica</option>
            <option value="Belize">Belize</option>
            <option value="Benin">Benin</option>
            <option value="Bielorrússia">Bielorrússia</option>
            <option value="Bolívia">Bolívia</option>
            <option value="Bósnia e Herzegovina">Bósnia e Herzegovina</option>
            <option value="Botsuana">Botsuana</option>
            <option value="Brunei">Brunei</option>
            <option value="Bulgária">Bulgária</option>
            <option value="Burkina Faso">Burkina Faso</option>
            <option value="Burundi">Burundi</option>
            <option value="Butão">Butão</option>
            <option value="Cabo Verde">Cabo Verde</option>
            <option value="Camarões">Camarões</option>
            <option value="Camboja">Camboja</option>
            <option value="Canadá">Canadá</option>
            <option value="Catar">Catar</option>
            <option value="Cazaquistão">Cazaquistão</option>
            <option value="Chade">Chade</option>
            <option value="Chile">Chile</option>
            <option value="China">China</option>
            <option value="Chipre">Chipre</option>
            <option value="Cingapura">Cingapura</option>
            <option value="Colômbia">Colômbia</option>
            <option value="Comores">Comores</option>
            <option value="Congo">Congo</option>
            <option value="Coreia do Norte">Coreia do Norte</option>
            <option value="Coreia do Sul">Coreia do Sul</option>
            <option value="Costa do Marfim">Costa do Marfim</option>
            <option value="Costa Rica">Costa Rica</option>
            <option value="Croácia">Croácia</option>
            <option value="Cuba">Cuba</option>
            <option value="Dinamarca">Dinamarca</option>
            <option value="Djibuti">Djibuti</option>
            <option value="Dominica">Dominica</option>
            <option value="Egito">Egito</option>
            <option value="El Salvador">El Salvador</option>
            <option value="Emirados Árabes Unidos">Emirados Árabes Unidos</option>
            <option value="Equador">Equador</option>
            <option value="Eritreia">Eritreia</option>
            <option value="Eslováquia">Eslováquia</option>
            <option value="Eslovênia">Eslovênia</option>
            <option value="Espanha">Espanha</option>
            <option value="Estados Unidos">Estados Unidos</option>
            <option value="Estônia">Estônia</option>
            <option value="Eswatini">Eswatini</option>
            <option value="Etiópia">Etiópia</option>
            <option value="Fiji">Fiji</option>
            <option value="Filipinas">Filipinas</option>
            <option value="Finlândia">Finlândia</option>
            <option value="França">França</option>
            <option value="Gabão">Gabão</option>
            <option value="Gâmbia">Gâmbia</option>
            <option value="Gana">Gana</option>
            <option value="Geórgia">Geórgia</option>
            <option value="Granada">Granada</option>
            <option value="Grécia">Grécia</option>
            <option value="Guatemala">Guatemala</option>
            <option value="Guiana">Guiana</option>
            <option value="Guiné">Guiné</option>
            <option value="Guiné-Bissau">Guiné-Bissau</option>
            <option value="Guiné Equatorial">Guiné Equatorial</option>
            <option value="Haiti">Haiti</option>
            <option value="Honduras">Honduras</option>
            <option value="Hungria">Hungria</option>
            <option value="Iêmen">Iêmen</option>
            <option value="Ilhas Marshall">Ilhas Marshall</option>
            <option value="Ilhas Salomão">Ilhas Salomão</option>
            <option value="Índia">Índia</option>
            <option value="Indonésia">Indonésia</option>
            <option value="Irã">Irã</option>
            <option value="Iraque">Iraque</option>
            <option value="Irlanda">Irlanda</option>
            <option value="Islândia">Islândia</option>
            <option value="Israel">Israel</option>
            <option value="Itália">Itália</option>
            <option value="Jamaica">Jamaica</option>
            <option value="Japão">Japão</option>
            <option value="Jordânia">Jordânia</option>
            <option value="Kosovo">Kosovo</option>
            <option value="Kuwait">Kuwait</option>
            <option value="Laos">Laos</option>
            <option value="Lesoto">Lesoto</option>
            <option value="Letônia">Letônia</option>
            <option value="Líbano">Líbano</option>
            <option value="Libéria">Libéria</option>
            <option value="Líbia">Líbia</option>
            <option value="Liechtenstein">Liechtenstein</option>
            <option value="Lituânia">Lituânia</option>
            <option value="Luxemburgo">Luxemburgo</option>
            <option value="Macedônia do Norte">Macedônia do Norte</option>
            <option value="Madagascar">Madagascar</option>
            <option value="Malásia">Malásia</option>
            <option value="Malauí">Malauí</option>
            <option value="Maldivas">Maldivas</option>
            <option value="Mali">Mali</option>
            <option value="Malta">Malta</option>
            <option value="Marrocos">Marrocos</option>
            <option value="Maurício">Maurício</option>
            <option value="Mauritânia">Mauritânia</option>
            <option value="México">México</option>
            <option value="Mianmar">Mianmar</option>
            <option value="Micronésia">Micronésia</option>
            <option value="Moçambique">Moçambique</option>
            <option value="Moldávia">Moldávia</option>
            <option value="Mônaco">Mônaco</option>
            <option value="Mongólia">Mongólia</option>
            <option value="Montenegro">Montenegro</option>
            <option value="Namíbia">Namíbia</option>
            <option value="Nauru">Nauru</option>
            <option value="Nepal">Nepal</option>
            <option value="Nicarágua">Nicarágua</option>
            <option value="Níger">Níger</option>
            <option value="Nigéria">Nigéria</option>
            <option value="Noruega">Noruega</option>
            <option value="Nova Zelândia">Nova Zelândia</option>
            <option value="Omã">Omã</option>
            <option value="Países Baixos">Países Baixos</option>
            <option value="Palau">Palau</option>
            <option value="Panamá">Panamá</option>
            <option value="Papua-Nova Guiné">Papua-Nova Guiné</option>
            <option value="Paquistão">Paquistão</option>
            <option value="Paraguai">Paraguai</option>
            <option value="Peru">Peru</option>
            <option value="Polônia">Polônia</option>
            <option value="Portugal">Portugal</option>
            <option value="Quênia">Quênia</option>
            <option value="Quirguistão">Quirguistão</option>
            <option value="Reino Unido">Reino Unido</option>
            <option value="República Centro-Africana">República Centro-Africana</option>
            <option value="República Dominicana">República Dominicana</option>
            <option value="República Tcheca">República Tcheca</option>
            <option value="Romênia">Romênia</option>
            <option value="Ruanda">Ruanda</option>
            <option value="Rússia">Rússia</option>
            <option value="Samoa">Samoa</option>
            <option value="San Marino">San Marino</option>
            <option value="Santa Lúcia">Santa Lúcia</option>
            <option value="São Cristóvão e Nevis">São Cristóvão e Nevis</option>
            <option value="São Tomé e Príncipe">São Tomé e Príncipe</option>
            <option value="São Vicente e Granadinas">São Vicente e Granadinas</option>
            <option value="Senegal">Senegal</option>
            <option value="Serra Leoa">Serra Leoa</option>
            <option value="Sérvia">Sérvia</option>
            <option value="Seychelles">Seychelles</option>
            <option value="Singapura">Singapura</option>
            <option value="Síria">Síria</option>
            <option value="Somália">Somália</option>
            <option value="Sri Lanka">Sri Lanka</option>
            <option value="Sudão">Sudão</option>
            <option value="Sudão do Sul">Sudão do Sul</option>
            <option value="Suécia">Suécia</option>
            <option value="Suíça">Suíça</option>
            <option value="Suriname">Suriname</option>
            <option value="Tailândia">Tailândia</option>
            <option value="Tajiquistão">Tajiquistão</option>
            <option value="Tanzânia">Tanzânia</option>
            <option value="Timor-Leste">Timor-Leste</option>
            <option value="Togo">Togo</option>
            <option value="Tonga">Tonga</option>
            <option value="Trinidad e Tobago">Trinidad e Tobago</option>
            <option value="Tunísia">Tunísia</option>
            <option value="Turcomenistão">Turcomenistão</option>
            <option value="Turquia">Turquia</option>
            <option value="Tuvalu">Tuvalu</option>
            <option value="Ucrânia">Ucrânia</option>
            <option value="Uganda">Uganda</option>
            <option value="Uruguai">Uruguai</option>
            <option value="Uzbequistão">Uzbequistão</option>
            <option value="Vanuatu">Vanuatu</option>
            <option value="Vaticano">Vaticano</option>
            <option value="Venezuela">Venezuela</option>
            <option value="Vietnã">Vietnã</option>
            <option value="Zâmbia">Zâmbia</option>
            <option value="Zimbábue">Zimbábue</option>
        </select>
        <style>
            #<?= $fieldName ?>_nationality:focus {
                opacity: 1 !important;
                border-color: <?= $customization['primary_color'] ?> !important;
            }
        </style>
    </div>

    <!-- Órgão Expedidor -->
    <div>
        <label class="block text-sm font-medium mb-2" style="color: <?= $customization['text_color'] ?>; opacity: 0.8;">
            Órgão Expedidor
        </label>
        <select name="<?= $fieldName ?>[issuing_agency]"
                id="<?= $fieldName ?>_issuing_agency"
                <?= $field['required'] ? 'required' : '' ?>
                class="w-full px-4 py-3 text-lg border-b-2 border-gray-300 dark:border-zinc-600 bg-transparent focus:outline-none focus:border-current transition-colors"
                style="border-color: <?= $customization['text_color'] ?>; opacity: 0.3;">
            <option value="">Selecione...</option>
            <option value="SSP">SSP - Secretaria de Segurança Pública</option>
            <option value="PM">PM - Polícia Militar</option>
            <option value="PC">PC - Polícia Civil</option>
            <option value="CNT">CNT - Carteira Nacional de Trabalho</option>
            <option value="DPF">DPF - Departamento de Polícia Federal</option>
            <option value="DETRAN">DETRAN - Departamento de Trânsito</option>
            <option value="IFP">IFP - Instituto Félix Pacheco</option>
            <option value="IML">IML - Instituto Médico Legal</option>
            <option value="EB">EB - Exército Brasileiro</option>
            <option value="FAB">FAB - Força Aérea Brasileira</option>
            <option value="MB">MB - Marinha do Brasil</option>
            <option value="MAER">MAER - Ministério da Aeronáutica</option>
            <option value="MEX">MEX - Ministério do Exército</option>
            <option value="MMA">MMA - Ministério da Marinha</option>
            <option value="OAB">OAB - Ordem dos Advogados do Brasil</option>
            <option value="CRM">CRM - Conselho Regional de Medicina</option>
            <option value="CREA">CREA - Conselho Regional de Engenharia e Agronomia</option>
            <option value="CRC">CRC - Conselho Regional de Contabilidade</option>
            <option value="COREN">COREN - Conselho Regional de Enfermagem</option>
            <option value="CRF">CRF - Conselho Regional de Farmácia</option>
            <option value="CRO">CRO - Conselho Regional de Odontologia</option>
        </select>
        <style>
            #<?= $fieldName ?>_issuing_agency:focus {
                opacity: 1 !important;
                border-color: <?= $customization['primary_color'] ?> !important;
            }
        </style>
    </div>

    <!-- UF de Expedição -->
    <div>
        <label class="block text-sm font-medium mb-2" style="color: <?= $customization['text_color'] ?>; opacity: 0.8;">
            UF de Expedição
        </label>
        <select name="<?= $fieldName ?>[issuing_state]"
                id="<?= $fieldName ?>_issuing_state"
                <?= $field['required'] ? 'required' : '' ?>
                class="w-full px-4 py-3 text-lg border-b-2 border-gray-300 dark:border-zinc-600 bg-transparent focus:outline-none focus:border-current transition-colors"
                style="border-color: <?= $customization['text_color'] ?>; opacity: 0.3;">
            <option value="">Selecione...</option>
            <option value="AC">AC - Acre</option>
            <option value="AL">AL - Alagoas</option>
            <option value="AP">AP - Amapá</option>
            <option value="AM">AM - Amazonas</option>
            <option value="BA">BA - Bahia</option>
            <option value="CE">CE - Ceará</option>
            <option value="DF">DF - Distrito Federal</option>
            <option value="ES">ES - Espírito Santo</option>
            <option value="GO">GO - Goiás</option>
            <option value="MA">MA - Maranhão</option>
            <option value="MT">MT - Mato Grosso</option>
            <option value="MS">MS - Mato Grosso do Sul</option>
            <option value="MG">MG - Minas Gerais</option>
            <option value="PA">PA - Pará</option>
            <option value="PB">PB - Paraíba</option>
            <option value="PR">PR - Paraná</option>
            <option value="PE">PE - Pernambuco</option>
            <option value="PI">PI - Piauí</option>
            <option value="RJ">RJ - Rio de Janeiro</option>
            <option value="RN">RN - Rio Grande do Norte</option>
            <option value="RS">RS - Rio Grande do Sul</option>
            <option value="RO">RO - Rondônia</option>
            <option value="RR">RR - Roraima</option>
            <option value="SC">SC - Santa Catarina</option>
            <option value="SP">SP - São Paulo</option>
            <option value="SE">SE - Sergipe</option>
            <option value="TO">TO - Tocantins</option>
        </select>
        <style>
            #<?= $fieldName ?>_issuing_state:focus {
                opacity: 1 !important;
                border-color: <?= $customization['primary_color'] ?> !important;
            }
        </style>
    </div>

    <!-- Data de Expedição -->
    <div>
        <label class="block text-sm font-medium mb-2" style="color: <?= $customization['text_color'] ?>; opacity: 0.8;">
            Data de Expedição
        </label>
        <input type="text"
               name="<?= $fieldName ?>[issue_date]"
               id="<?= $fieldName ?>_issue_date"
               placeholder="DD/MM/AAAA"
               <?= $field['required'] ? 'required' : '' ?>
               class="w-full px-4 py-3 text-lg border-b-2 border-gray-300 dark:border-zinc-600 bg-transparent focus:outline-none focus:border-current transition-colors date-mask"
               style="border-color: <?= $customization['text_color'] ?>; opacity: 0.3;"
               maxlength="10">
        <style>
            #<?= $fieldName ?>_issue_date:focus {
                opacity: 1 !important;
                border-color: <?= $customization['primary_color'] ?> !important;
            }
        </style>
    </div>
</div>

<script>
function showRgComplementaryFields(fieldName) {
    const rgInput = document.getElementById(fieldName);
    const complementaryDiv = document.getElementById(fieldName + '_complementary');

    // Mostrar campos complementares quando o RG tiver pelo menos 8 dígitos
    if (rgInput && complementaryDiv && rgInput.value.replace(/\D/g, '').length >= 8) {
        complementaryDiv.style.display = 'block';

        // Aplicar máscaras nos campos de data
        if (typeof InputMasks !== 'undefined') {
            const birthDate = document.getElementById(fieldName + '_birth_date');
            const issueDate = document.getElementById(fieldName + '_issue_date');
            if (birthDate) InputMasks.date(birthDate);
            if (issueDate) InputMasks.date(issueDate);
        }
    }
}
</script>
<?php endif; ?>
