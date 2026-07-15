define(['jquery', 'core/str'], function($, Str) {

    var labels = {};

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
        $('#progress-bar-fill').css('width', pct + '%').css('background', '#007bff');
        $('#progress-title').text(labels.progressscanning + ' (' + data.files + ' ' + labels.progressfiles.toLowerCase() + ')');
        $('#progress-stats').html(
            '<span>' + labels.progresscourses + ': ' + data.courses + '</span>' +
            ' <span>' + labels.progresssections + ': ' + data.sections + '</span>' +
            ' <span>' + labels.progressfiles + ': ' + data.files + '</span>'
        );
        $('#progress-status').text(labels.progressready);
    }

    function resetUI(submitBtn) {
        $('#progress-modal').hide();
        if (submitBtn) {
            submitBtn.prop('disabled', false).val(submitBtn.data('original-value'));
        }
        $('#progress-bar-fill').css('width', '0%').css('background', '#007bff');
        $('select[name="courseid"], select[name="categoryid"]').val('');
        $('#reset-export-btn').remove();
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

    function buildCourseItem(course, state) {
        var icons = {ready: '\u2713', downloading: '\u25B6', pending: '\u25CB'};
        var iconClasses = {ready: 'ci-ready', downloading: 'ci-dl', pending: 'ci-pend'};
        var btnHtml;
        if (state === 'pending') {
            btnHtml = '<button class="c-dl-btn" data-courseid="' + course.id + '">Descargar</button>';
        } else if (state === 'downloading') {
            btnHtml = '<button class="c-dl-btn" disabled>Descargando...</button>';
        } else {
            btnHtml = '<span class="c-done">\u2713 Completado</span>';
        }
        return '<div class="c-item" data-courseid="' + course.id + '">'
            + '<div class="c-icon ' + iconClasses[state] + '">' + icons[state] + '</div>'
            + '<div class="c-name">' + course.fullname + '</div>'
            + btnHtml
            + '</div>';
    }

    function showCourseList(courselist, form) {
        var submitBtn = $(form).find('input[type="submit"]');

        $('#progress-modal').show();
        $('#progress-bar-fill').css('width', '0%').css('background', '#007bff');
        $('#progress-title').text('Cursos disponibles: ' + courselist.length);
        $('#progress-status').text('Seleccione los cursos a descargar');

        var html = '<div class="course-progress-header">'
            + '<div class="c-count" id="courses-count">Cursos disponibles: ' + courselist.length + '</div>'
            + '</div>'
            + '<div class="course-list" id="course-list-container">';
        for (var i = 0; i < courselist.length; i++) {
            html += buildCourseItem(courselist[i], 'pending');
        }
        html += '</div>';

        if (!$('#reset-export-btn').length) {
            html += '<button id="reset-export-btn" style="margin-top:1em;padding:.6em 1.5em;background:#0f6cbf;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:.9em;font-weight:500;width:100%">Realizar otra exportaci\u00f3n</button>';
        }

        $('#progress-stats').html(html);

        $(document).off('click', '.c-dl-btn').on('click', '.c-dl-btn', function() {
            var courseId = $(this).data('courseid');
            downloadSingleCourse(courseId, courselist);
        });

        $(document).off('click', '#reset-export-btn').on('click', '#reset-export-btn', function() {
            resetUI(submitBtn);
        });
    }

    function downloadSingleCourse(courseId, courselist) {
        var item = $('.c-item[data-courseid="' + courseId + '"]');
        if (!item.length || item.find('.c-dl-btn[disabled]').length) {
            return;
        }

        item.find('.c-icon').removeClass('ci-ready ci-pend').addClass('ci-dl').text('\u25B6');
        item.find('.c-dl-btn').prop('disabled', true).text('Descargando...');

        var sesskey = $('#form-category input[name="sesskey"]').val();
        if (!sesskey) {
            sesskey = $('#form-course input[name="sesskey"]').val();
        }
        var url = 'export.php?action=download_course&courseid=' + courseId + '&sesskey=' + sesskey;
        triggerDownload(url);

        setTimeout(function() {
            item.find('.c-icon').removeClass('ci-dl').addClass('ci-ready').text('\u2713');
            item.find('.c-dl-btn').replaceWith('<span class="c-done">\u2713 Completado</span>');

            var remaining = $('.c-dl-btn').length;
            var total = $('.c-item').length;
            $('#courses-count').text('Descargados: ' + (total - remaining) + ' de ' + total);

            if (remaining === 0) {
                $('#progress-title').text('Descarga completada');
                $('#progress-status').text('Todos los cursos se descargaron correctamente');
                $('#progress-bar-fill').css('width', '100%').css('background', '#28a745');
            }
        }, 2000);
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
                showCourseList(data.courselist, form);
                submitBtn.prop('disabled', false).val(submitBtn.data('original-value'));
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
