define(['jquery', 'core/str'], function($, Str) {

    var labels = {};
    var currentState = null;

    function loadStrings() {
        var keys = [
            'progressscanning', 'progressready', 'progresscourses', 'progresssections',
            'progressfiles', 'exportinprogress'
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

    function showScanProgress(data) {
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
    }

    function updateCategoryProgress(index, total, courselist) {
        var html = '<div style="margin-bottom:.5em;font-weight:600">Descargados: ' + index + ' de ' + total + '</div>';
        html += '<ul style="font-size:.9em;list-style:none;padding:0">';
        for (var i = 0; i < courselist.length; i++) {
            var c = courselist[i];
            var icon, style;
            if (i < index) {
                icon = '\u2713';
                style = 'color:#28a745';
            } else if (i === index) {
                icon = '\u2190';
                style = 'color:#0f6cbf;font-weight:600';
            } else {
                icon = '\u23F3';
                style = 'color:#999';
            }
            html += '<li style="' + style + ';margin:.2em 0">' + icon + ' ' + c.fullname + '</li>';
        }
        html += '</ul>';
        $('#progress-stats').html(html);
        $('#progress-title').text('Descargando cursos...');
        $('#progress-status').text('Curso ' + (index + 1) + ' de ' + total);

        var pct = total > 0 ? Math.round((index / total) * 100) : 0;
        $('#progress-bar-fill').css('width', pct + '%');
    }

    function resetUI(submitBtn) {
        $('#progress-modal').hide();
        if (submitBtn) {
            submitBtn.prop('disabled', false).val(submitBtn.data('original-value'));
        }
        $('#progress-bar-fill').css('width', '0%').css('background', '#007bff');
        $('select[name="courseid"], select[name="categoryid"]').val('');
    }

    function triggerDownload(url) {
        var a = document.createElement('a');
        a.href = url;
        a.download = '';
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }

    function startCategoryDownload(courselist, form) {
        var index = 0;
        var total = courselist.length;
        var sesskey = $(form).find('input[name="sesskey"]').val();
        var submitBtn = $(form).find('input[type="submit"]');

        $('#progress-bar-fill').css('background', '#28a745');

        function downloadNext() {
            if (index >= total) {
                $('#progress-title').text('Descarga completada');
                $('#progress-status').text('Se descargaron ' + total + ' cursos');
                $('#progress-bar-fill').css('width', '100%');
                updateCategoryProgress(total, total, courselist);
                if (!$('#reset-export-btn').length) {
                    var btn = $('<button id="reset-export-btn" style="margin-top:1em;padding:.5em 1.5em;background:#0f6cbf;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:1em">Realizar otra exportaci\u00f3n</button>');
                    btn.on('click', function() {
                        resetUI(submitBtn);
                        $(this).remove();
                    });
                    $('#progress-stats').append(btn);
                }
                return;
            }

            updateCategoryProgress(index, total, courselist);

            var course = courselist[index];
            var url = 'export.php?action=download_course&courseid=' + course.id + '&sesskey=' + sesskey;
            triggerDownload(url);

            index++;
            setTimeout(downloadNext, 3000);
        }

        downloadNext();
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
            if (data.courselist && data.courselist.length > 0) {
                showScanProgress(data);
                setTimeout(function() {
                    startCategoryDownload(data.courselist, form);
                }, 1500);
            } else {
                showScanProgress(data);
                setTimeout(function() {
                    form.submit();
                }, 1500);
                setTimeout(function() {
                    resetUI(submitBtn);
                }, 3500);
            }
        }).fail(function(jqXHR) {
            var msg = jqXHR.responseJSON ? (jqXHR.responseJSON.error || jqXHR.responseJSON.message) : null;
            $('#progress-status').text(msg || (jqXHR.responseText || labels.progressready));
            $('#progress-bar-fill').css('width', '100%').css('background', '#dc3545');
            submitBtn.prop('disabled', false).val(submitBtn.data('original-value'));
            setTimeout(function() {
                form.submit();
            }, 3000);
        });
    }

    return {
        init: function() {
            loadStrings().then(function() {
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
