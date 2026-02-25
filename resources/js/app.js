import './bootstrap';
import '@fortawesome/fontawesome-free/css/all.min.css';
import 'tom-select/dist/css/tom-select.default.css';
import TomSelect from 'tom-select';
window.TomSelect = TomSelect;
window.initTomSelect = function (elementId, modelName, data, labelField = 'name') {
    const el = document.getElementById(elementId);
    if (!el) return;

    if (el.tomselect) {
        el.tomselect.sync();
        return;
    }

    let tom = new TomSelect(el, {
        options: data,
        valueField: 'id',
        labelField: labelField,
        searchField: [labelField],
        create: false,
        placeholder: el.getAttribute('placeholder'),
        allowEmptyOption: true,
        items: [window.Livewire.find(el.closest('[wire\\:id]').getAttribute('wire:id')).get(modelName)],
        onChange(value) {
            window.Livewire.find(el.closest('[wire\\:id]').getAttribute('wire:id')).set(modelName, value);
        },
    });

    window.Livewire.find(el.closest('[wire\\:id]').getAttribute('wire:id'))
        .on('resetTomSelect', () => tom.clear());
};
