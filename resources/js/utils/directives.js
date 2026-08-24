export function registerDirectives(app) {
    app.directive('forminput', {
        beforeMount: function (el) {
            el.classList.add('w-full');
            el.classList.add('rounded-md');
            el.classList.add('border-gray-300');
            el.classList.add('text-sm');
            el.classList.add('p-2');
        }
    });

    app.directive('formcheckbox', {
        beforeMount: function (el) {
            el.classList.add('focus:ring-indigo-500');
            el.classList.add('h-4');
            el.classList.add('w-4');
            el.classList.add('text-indigo-600');
            el.classList.add('border-gray-300');
            el.classList.add('rounded-md');
        }
    });

    app.directive('formselect', {
        beforeMount: function (el) {
            el.classList.add('border');
            el.classList.add('border-gray-300');
            el.classList.add('rounded-md');
            el.classList.add('text-sm');
            el.classList.add('py-2');
        }
    });

    app.directive('formlabel', {
        beforeMount: function (el) {
            el.classList.add('block');
            el.classList.add('font-semibold');
            el.classList.add('text-sm');
            el.classList.add('text-gray-900');
            el.classList.add('mb-2');
        }
    });
}
