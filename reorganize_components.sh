#!/bin/bash

# Создаем необходимые папки
mkdir -p resources/js/Components/UI/Buttons
mkdir -p resources/js/Components/UI/Forms
mkdir -p resources/js/Components/UI/Modals
mkdir -p resources/js/Components/UI/Feedback
mkdir -p resources/js/Components/UI/Navigation

# Перемещаем кнопки
mv resources/js/Components/PrimaryButton.vue resources/js/Components/UI/Buttons/ 2>/dev/null || true
mv resources/js/Components/SecondaryButton.vue resources/js/Components/UI/Buttons/ 2>/dev/null || true
mv resources/js/Components/DangerButton.vue resources/js/Components/UI/Buttons/ 2>/dev/null || true

# Перемещаем формы
mv resources/js/Components/TextInput.vue resources/js/Components/UI/Forms/ 2>/dev/null || true
mv resources/js/Components/InputLabel.vue resources/js/Components/UI/Forms/ 2>/dev/null || true
mv resources/js/Components/InputError.vue resources/js/Components/UI/Forms/ 2>/dev/null || true
mv resources/js/Components/Checkbox.vue resources/js/Components/UI/Forms/ 2>/dev/null || true

# Перемещаем модальные окна
mv resources/js/Components/Modal.vue resources/js/Components/UI/Modals/ 2>/dev/null || true
mv resources/js/Components/DialogModal.vue resources/js/Components/UI/Modals/ 2>/dev/null || true
mv resources/js/Components/ConfirmationModal.vue resources/js/Components/UI/Modals/ 2>/dev/null || true
mv resources/js/Components/ConfirmsPassword.vue resources/js/Components/UI/Modals/ 2>/dev/null || true

# Перемещаем feedback компоненты
mv resources/js/Components/Banner.vue resources/js/Components/UI/Feedback/ 2>/dev/null || true
mv resources/js/Components/ActionMessage.vue resources/js/Components/UI/Feedback/ 2>/dev/null || true

# Перемещаем навигацию
mv resources/js/Components/Dropdown.vue resources/js/Components/UI/Navigation/ 2>/dev/null || true
mv resources/js/Components/DropdownLink.vue resources/js/Components/UI/Navigation/ 2>/dev/null || true

# Оставляем в корне только секционные компоненты
# FormSection, ActionSection, SectionTitle, SectionBorder
# AuthenticationCard, AuthenticationCardLogo

echo "Компоненты успешно реорганизованы!"
