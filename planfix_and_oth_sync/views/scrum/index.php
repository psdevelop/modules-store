<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.6.0/Chart.bundle.min.js"></script>

<h1><?= $sprintData['sprint']['Название спринта'] ?></h1>

<a class="btn btn-warning" href="https://leads.planfix.ru/?action=tasks&filter=5167978" target="_blank">
    <i class="glyphicon glyphicon-flag"></i>
    Планфикс!
</a>
<span class="badge"><?= $sprintData['sprint_long'] ?> дн.</span>
<h5><?= $sprintData['sprint_begin'] ?> - <?= $sprintData['sprint_end'] ?></h5>
<?php foreach ($sprints as $name => $sprintItem) {


    ; ?>
    <div class="btn-group">
        <?php if (isset($sprintItem['key']) && $sprintItem['key'] == $sprintData['sprint_key']) {
            ; ?>
            <a class="btn btn-sm btn-info active" href="?sid=<?= $sprintItem['key'] ?>">
                <?= $sprintItem['Название спринта'] ?>
            </a>
            <a class="btn btn-sm <?php if ($sprintData['sprint_cached']):; ?>btn-danger<?php else: ; ?>btn-default<?php endif; ?>"
               onclick="scrum.toggleSprint($(this))"
               data-sprint="<?= $sprintItem['key'] ?>"
               data-cached="<?= (bool)$sprintData['sprint_cached'] ? 'true' : 'false' ?>"
               href="#">
                🔒
            </a>
        <?php } else {
            ; ?>
            <a class="btn btn-sm btn-info"
               data-sprint="<?= $sprintItem['key'] ?>"
               href="?sid=<?= $sprintItem['key'] ?>"><?= $sprintItem['Название спринта'] ?></a>
        <?php }; ?>
    </div>
<?php }; ?>

<div class="chart-container">
    <canvas id="chart"></canvas>
</div>

<div class="badge">TEST</div>
<table class="table-condensed" style="font-size: small">
    <tbody>
    <?php foreach ($tasks as $id => $task) {
        ; ?>
        <tr <?php if ($task['type'] ?? null == 'Баг') {
            echo 'style="color:red"';
        }; ?>
        >
            <td>
                <a target="_blank"
                   href="<?= "https://leads.planfix.ru/task/" . $task['general'] ?>"><?= $task['general'] ?></a>
            </td>
            <td>
                <?= $task['title']; ?>
            </td>
            <td>
                <?= $task['points'] ?? ""; ?>
            </td>
            <td width="10%">
                <?= $task['endTime'] ?? null; ?>
            </td>
            <td>
                <?= $task['type'] ?? null; ?>
            </td>
            <td>
                <?= $task['humanStatus'] ?? null; ?>
            </td>
            <td>
                <?= $task['sprint'] ?? null; ?>
            </td>
        </tr>
    <?php }; ?>
    </tbody>
</table>

<script>
    var ctx = document.getElementById("chart").getContext('2d');
    var pointsBarChart = new Chart(ctx, {
        type: 'bar',
        showTooltips: true,
        data: {
            labels: <?=json_encode($labels);?>,
            datasets: [
                {
                    label: 'mid',
                    type: 'line',
                    data: <?=json_encode($plans);?>,
                    borderWidth: 2,
                    lineTension: 0,
                    borderColor: 'black'
                },
                <?php foreach ($model as $key => $data) {
                if ($key == 'total') {
                    continue;
                }
                echo "
                        {
                            label: '" . $types[$key]['label'] . "',
                            data: " . json_encode(array_values($data)) . ",
                            borderWidth: 3,
                            lineTension: 0,
                            borderColor: '" . $types[$key]['lineColor'] . "',
                            backgroundColor: '" . $types[$key]['color'] . "',
                            pointStyle: 'crossRot',
                        },
                    ";
            }
                ?>
            ]
        },
        options: {
            scales: {
                yAxes: [{
                    stacked: true,
                    ticks: {
                        beginAtZero: true
                    },
                    gridLines: {
                        display: true
                    },
                    id: "y-axis-0"
                }],
                xAxes: [{
                    stacked: true,
                    ticks: {
                        beginAtZero: true
                    }
                }]
            }
        }
    });

</script>

<script>
    var scrum = {
        toggleSprint: function (object) {
            var cached = object.attr('data-cached');
            if (cached === 'true') {
                admin.query('scrum', 'un-fix-sprint', {
                    id: object.attr('data-sprint')
                }, scrum.unFixSprint);
            } else {
                admin.query('scrum', 'fix-sprint', {
                    id: object.attr('data-sprint')
                }, scrum.fixSprint);
            }
        },
        fixSprint: function (response) {
            if (response.success === true) {
                admin.flash('success', response.message);
                var toggle = $('[data-sprint=' + response.id + ']');
                toggle.addClass('btn-danger');
                toggle.attr('data-cached', true);
            }
        },
        unFixSprint: function (response) {
            if (response.success === true) {
                admin.flash('success', response.message);
                var toggle = $('[data-sprint=' + response.id + ']');
                toggle.removeClass('btn-danger');
                toggle.attr('data-cached', false);
            }
        }
    };

    var admin = {
        query: function (controller, method, params, callback) {
            $.ajax({
                type: "POST",
                url: controller + '/' + method + '/',
                data: params,
                dataType: 'json',
                success: function (response) {
                    if (response.success === true) {
                        callback(response);
                    } else {
                        admin.flash('danger', response.error)
                    }
                }
            });
        },
        console: function (data) {
            console.log(data);
        },
        flash: function (level, message) {
            var flashMessage = $('<div />', {
                'class': 'alert alert-' + level,
                'style': 'position: absolute; width: 100%',
                text: message
            }).show().fadeIn('fast');

            $('body').prepend(flashMessage);
            flashMessage.delay(1000).fadeOut('normal', function () {
                $(this).remove();
            });
            console.log(flashMessage);
        }
    };
</script>