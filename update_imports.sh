#!/bin/bash

# Функция для обновления импортов в файле
update_file() {
    file=$1
    # Buttons
    sed -i "s|from '@/Components/PrimaryButton.vue'|from '@/Components/UI/Buttons/PrimaryButton.vue'|g" "$file"
    sed -i "s|from '@/Components/SecondaryButton.vue'|from '@/Components/UI/Buttons/SecondaryButton.vue'|g" "$file"
    sed -i "s|from '@/Components/DangerButton.vue'|from '@/Components/UI/Buttons/DangerButton.vue'|g" "$file"
    
    # Forms
    sed -i "s|from '@/Components/TextInput.vue'|from '@/Components/UI/Forms/TextInput.vue'|g" "$file"
    sed -i "s|from '@/Components/InputLabel.vue'|from '@/Components/UI/Forms/InputLabel.vue'|g" "$file"
    sed -i "s|from '@/Components/InputError.vue'|from '@/Components/UI/Forms/InputError.vue'|g" "$file"
    sed -i "s|from '@/Components/Checkbox.vue'|from '@/Components/UI/Forms/Checkbox.vue'|g" "$file"
    
    # Modals
    sed -i "s|from '@/Components/Modal.vue'|from '@/Components/UI/Modals/Modal.vue'|g" "$file"
    sed -i "s|from '@/Components/DialogModal.vue'|from '@/Components/UI/Modals/DialogModal.vue'|g" "$file"
    sed -i "s|from '@/Components/ConfirmationModal.vue'|from '@/Components/UI/Modals/ConfirmationModal.vue'|g" "$file"
    sed -i "s|from '@/Components/ConfirmsPassword.vue'|from '@/Components/UI/Modals/ConfirmsPassword.vue'|g" "$file"
    
    # Feedback
    sed -i "s|from '@/Components/Banner.vue'|from '@/Components/UI/Feedback/Banner.vue'|g" "$file"
    sed -i "s|from '@/Components/ActionMessage.vue'|from '@/Components/UI/Feedback/ActionMessage.vue'|g" "$file"
    
    # Navigation
    sed -i "s|from '@/Components/Dropdown.vue'|from '@/Components/UI/Navigation/Dropdown.vue'|g" "$file"
    sed -i "s|from '@/Components/DropdownLink.vue'|from '@/Components/UI/Navigation/DropdownLink.vue'|g" "$file"
}

# Обновляем все .vue файлы
find resources/js/Pages -name "*.vue" -type f | while read file; do
    echo "Обновление: $file"
    update_file "$file"
done

# Обновляем компоненты, которые могут импортировать другие компоненты
find resources/js/Components -name "*.vue" -type f | while read file; do
    echo "Обновление: $file"
    update_file "$file"
done

echo "✅ Все импорты обновлены!"
