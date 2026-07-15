define(['jquery', 'core/str'], function($, Str) {

    var labels = {};

    function loadStrings() {
        var keys = [
            'progressscanning', 'progressready', 'progresscourses', 'progresssections',
            'progressfiles', 'selectall', 'deselectall', 'largeexportwarning', 'exportinprogress'
        ];
        var promises = keys.map(function(k) {
            return Str.get_string(k, 'local_courseexport');
        });
        return $.when.apply($, promises).then(function() {
            var args = Array.prototype.slice.call(arguments);
            keys.forEach(function(k, i) {
                labels[k] = args[i];
            });
        });
    }

    function toggleFileTypes(link) {
        var formId = link.getAttribute('data-form');
        var checkboxes = $('#' + formId + ' input[name="filetypes[]"]');
        var allChecked = checkboxes.length === checkboxes.filter(':checked').length;
        checkboxes.prop('checked', !allChecked);
        link.textContent = allChecked ? labels.selectall : labels.deselectall;
    }

    function showProgress(data) {
        $('#progress-modal').show();

        var pct = data.files > 0 ? 100 : (data.sections > 0 ? 100 : (data.courses > 0 ? 100 : 0));

        $('#progress-bar-fill').css('width', pct + '%');
        $('#progress-title').text(labels.progressscanning + ' (' + data.files + ' ' + labels.progressfiles.toLowerCase() + ')');

        $('#progress-stats').html(
            '<span>' + labels.progresscourses + ': ' + data.courses + '</span>' +
            ' <span>' + labels.progresssections + ': ' + data.sections + '</span>' +
            ' <span>' + labels.progressfiles + ': ' + data.files + '</span>'
        );
        $('#progress-status').text(labels.progressready);

        if (data.files > 500) {
            $('#largeexport-warning').text(labels.largeexportwarning.replace('{$a}', data.files)).show();
        }
    }

    function doExport(form) {
        var formData = new FormData(form);
        formData.set('action', 'count');

        var submitBtn = $(form).find('input[type="submit"]');
        submitBtn.prop('disabled', true).val(labels.exportinprogress);

        $('#progress-modal').show();
        $('#progress-bar-fill').css('width', '10%');
        $('#progress-title').text(labels.progressscanning);
        $('#progress-status').text('');

        $.ajax({
            url: form.action,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json'
        }).done(function(data) {
            showProgress(data);
            setTimeout(function() {
                form.submit();
            }, 1500);

            setTimeout(function() {
                $('#progress-modal').hide();
                $('#largeexport-warning').hide();
                submitBtn.prop('disabled', false).val(submitBtn.data('original-value'));
                $('#progress-bar-fill').css('background', '#007bff');
                $('select[name="courseid"], select[name="categoryid"]').val('');
            }, 3500);
        }).fail(function(jqXHR) {
            var msg = jqXHR.responseJSON ? (jqXHR.responseJSON.error || jqXHR.responseJSON.message) : null;
            $('#progress-status').text(msg || (jqXHR.responseText || labels.progressready));
            $('#progress-bar-fill').css('width', '100%').css('background', '#dc3545');
            submitBtn.prop('disabled', false).val(submitBtn.data('original-value') || 'Export');
            setTimeout(function() {
                form.submit();
            }, 3000);
        });
    }

    return {
        init: function() {
            loadStrings().then(function() {
                $('.toggle-filetypes').on('click', function(e) {
                    e.preventDefault();
                    toggleFileTypes(this);
                });

                $('#form-course, #form-category').on('submit', function(e) {
                    e.preventDefault();
                    var btn = $(this).find('input[type="submit"]');
                    btn.data('original-value', btn.val());
                    doExport(this);
                });
            });
        }
    };
});
