<?php

namespace app\controllers;

use app\models\planfix\PlanfixAnalyticsHandbook;
use app\models\planfix\PlanfixBase;
use app\models\planfix\PlanfixTask;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;

class ScrumController extends Controller
{
    public $currentValue = 0;
    public $prevValue = 0;
    protected $cacheDirectory = __DIR__ . '/../../runtime/scrum/';


    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Патч для ajax. Надо как-то иначе...
     * @param \yii\base\Action $action
     * @return bool
     */
    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    /**
     * Зафиксировать данные спринта
     * @param $data
     * @param $key
     * @throws \Exception
     */
    protected function fixSprintData($data, $key)
    {
        /**
         * Директория для кэширование
         */
        if (!file_exists($this->cacheDirectory) && !mkdir($this->cacheDirectory)) {
            throw new \Exception($this->cacheDirectory . ' can not be written...');
        }

        /**
         * Файл для кэширование
         */
        $cacheFile = $this->cacheDirectory . '/' . $key;
        if (!file_exists($cacheFile) && !touch($cacheFile)) {
            throw new \Exception($cacheFile . ' can not be written...');
        }

        file_put_contents($cacheFile, json_encode($data));
    }

    /**
     * Сбросить данные спринта
     * @param $key
     * @return bool
     */
    protected function unFixSprintData($key)
    {
        /**
         * Директория для кэширование
         */
        if (!file_exists($this->cacheDirectory) && !mkdir($this->cacheDirectory)) {
            return false;
        }

        /**
         * Файл для кэширование
         */
        $cacheFile = $this->cacheDirectory . '/' . $key;
        if (!file_exists($cacheFile)) {
            return false;
        }

        return unlink($cacheFile);
    }

    /**
     * Поднять данные спринта
     * @param $key
     * @return mixed
     * @throws \Exception
     */
    protected function extractSprintData($key)
    {
        /**
         * Воссстановление из файла (опционального / по-умолчанию)
         */
        $cacheFile = $this->cacheDirectory . '/' . $key;
        if (!file_exists($cacheFile)) {
            return null;
        }
        $fileData = file_get_contents($cacheFile);
        return json_decode($fileData, true);
    }

    /**
     * @param $sprintId
     * @return string
     */
    protected function getCacheKey($sprintId)
    {
        return $cacheKey = "scrum_sprint_" . $sprintId;
    }

    /**
     * @return \yii\web\Response
     */
    public function actionUnFixSprint()
    {
        $request = \Yii::$app->request->post();
        if (!$id = $request['id'] ?? null) {
            return $this->asJson([
                'error' => 'Sprint not found!'
            ]);
        }
        $cacheKey = $this->getCacheKey($id);
        if (!$this->unFixSprintData($cacheKey)) {
            return $this->asJson([
                'id' => $id,
                'success' => true,
                'message' => "File cache is already does not exists!"
            ]);
        }
        return $this->asJson([
            'id' => $id,
            'success' => true,
            'message' => "Кэш сброшен!"
        ]);
    }

    /**
     * @return \yii\web\Response
     */
    public function actionFixSprint()
    {
        $request = \Yii::$app->request->post();
        if (!$id = $request['id'] ?? null) {
            return $this->asJson([
                'error' => 'Sprint not found!'
            ]);
        }
        $cacheKey = $this->getCacheKey($id);
        $this->fixSprintData(PlanfixTask::getScrumTasks($id), $cacheKey);
        return $this->asJson([
            'id' => $id,
            'success' => true,
            'message' => "Спринт закеширован!"
        ]);
    }

    /**
     * Displays homepage.
     * @param null $sid
     * @return string
     */
    public function actionIndex($sid = null, $limit = null)
    {
        $daySeconds = 24 * 60 * 60;
        $sprintsHandbook = PlanfixBase::instance()->handbooks['sprints'];
        $limitValue = $limit ?? 165;

        $typesConfig = [
            'total' => [
                'color' => '#000010',
                'lineColor' => '#fff',
                'label' => 'Всего',
                'k' => 1,
            ],
            'Плановая' => [
                'color' => '#70D889',
                'lineColor' => '#fff',
                'label' => 'Плановая',
                'k' => 1,
            ],
            'Рефакторинг' => [
                'color' => '#D64ED8',
                'lineColor' => '#fff',
                'label' => 'Рефакторинг',
                'k' => 1,
            ],
            'Системная' => [
                'color' => '#6990D8',
                'lineColor' => '#fff',
                'label' => 'Системная',
                'k' => 1,
            ],
            'Внеплановая' => [
                'color' => "#e0b924",
                'lineColor' => '#fff',
                'label' => 'Внеплановая',
                'k' => 1,
            ],
            'Баг' => [
                'color' => '#d83868',
                'lineColor' => '#fff',
                'label' => 'Баг',
                'k' => 1,
            ],
            'Внешний исполнитель' => [
                'color' => '#900000',
                'lineColor' => '#fff',
                'label' => 'Внешний исполнитель',
                'k' => 1,
            ],
            'Не определено' => [
                'color' => '#900000',
                'lineColor' => '#fff',
                'label' => 'Не определено',
                'k' => 1,
            ]
        ];

        /**
         * Используемые статусы
         */
        $statuses = [
            PlanfixTask::TASK_STATUS_COMPLETED => 'Завершенная',
            PlanfixTask::TASK_STATUS_DONE => 'Выполненная',
            126 => 'На демо!'
        ];


        /**
         * Спринты из справочника
         */
        $selectedSprints = PlanfixAnalyticsHandbook::getHandbook($sprintsHandbook, 'key');
        if (!$selectedSprint = PlanfixAnalyticsHandbook::getFromHandBook($sid)) {
            $selectedSprint = current($selectedSprints);
        }
        /**
         * все таски из фильтра кэш / запрос
         */
        $cacheKey = $this->getCacheKey($selectedSprint['key']);
        $sprintCached = true;
        if (!$tasks = $this->extractSprintData($cacheKey)) {
            $sprintCached = false;
            if (!$tasks = PlanfixTask::getScrumTasks($selectedSprint['key'])) {
                $tasks = [];
            }
        }

        $sprintStart = $selectedSprint['Начало'];
        $sprintEnd = $selectedSprint['Окончание'];
        $sprintLongDays = 1 + (strtotime($sprintEnd) - strtotime($sprintStart)) / $daySeconds;

        $sprintDays = [];

        for ($i = 0; $i <= $sprintLongDays - 1; $i++) {
            $sprintDays[date('d-m-Y', strtotime($sprintStart) + $daySeconds * $i)] = 0;
        }

        $report = $maskDays = $maskTypes = [];
        foreach ($typesConfig as $typeName => $type) {
            $maskDays[$typeName] = $sprintDays;
            $maskTypes[$typeName] = 0;
        }
        $report['burned'] = $report['burned_up'] = $report['not_burned'] = $report['normal'] = $maskDays;
        $report['totals'] = $maskTypes;
        $report['norms'] = $report['limits'] = $sprintDays;


        foreach ($tasks as $id => $task) {
            $task['status'] = (int)$task['status'];
            $tasks[$id]['humanStatus'] = $statuses[$task['status']] ?? '';
            $tasks[$id]['is_done'] = false;
            $task['points'] = (int)($task['points'] ?? null);
            $taskDay = $task['endTime'] = date('d-m-Y', strtotime($task['endTime']));
            $taskType = $task['type'] ?? null;

            if (!$task['points'] = (int)($task['points'] ?? null)) {
                continue;
            }
            $taskPointsValue = ($typesConfig[$taskType]['k'] ?? 0) * $task['points'];
            // Выполнена или завершена и в периоде спринта
            if (($task['status'] === 6 || $task['status'] === 3 || $task['status'] === 126 || $task['status'] === 110) && $taskDay && isset($sprintDays[$taskDay])) {
                $report['burned']['total'][$taskDay] += $taskPointsValue;
                $report['burned'][$taskType][$taskDay] += $taskPointsValue;
                $task['is_done'] = true;
            }

            if (!isset($report['totals'][$taskType])) {
                $report['totals'][$taskType] = null;
            }
            $report['totals'][$taskType] += $taskPointsValue;
            $report['totals']['total'] += $taskPointsValue;
        }


        $norm = $report['totals']['total'];

        $normByDay = ($sprintLongDays <= 1) ? 1 : $norm / ($sprintLongDays - 1);
        $planByDay = ($sprintLongDays <= 1) ? 1 : ($limitValue / ($sprintLongDays - 1));

        $iterator = 0;
        foreach ($report['norms'] as $day => &$value) {
            $value = $norm - $normByDay * $iterator;
            $iterator++;
        }
        unset($value);

        $iterator = 0;
        foreach ($report['limits'] as $day => &$value) {
            $value = $limitValue - $planByDay * $iterator;
            $iterator++;
        }
        unset($value);

        foreach ($report['burned'] as $type => $data) {
            $this->prevValue = 0;
            foreach ($report['burned'][$type] as $day => $value) {
                $this->prevValue += $value;
                $report['burned_up'][$type][$day] = $this->prevValue;
                $report['not_burned'][$type][$day] = $report['totals'][$type] - $this->prevValue;
                $report['not_burned']['total'][$day] = $report['totals']['total'] - $report['burned_up']['total'][$day];
            }
        }

        return $this->render('index', [
            'model' => $report['not_burned'],
            'modelUp' => $report['burned_up'],
            'types' => $typesConfig,
            'totals' => $report['totals'],
            'limits' => array_values($report['limits']),
            'plans' => array_values($report['norms']),
            'sprints' => $selectedSprints,
            'labels' => array_keys($report['not_burned']['total']),
            'tasks' => $tasks,
            'sprintData' => [
                'sprint_cached' => $sprintCached,
                'sprint_begin' => $sprintStart,
                'sprint_end' => $sprintEnd,
                'sprint_name' => $selectedSprint,
                'sprint_long' => $sprintLongDays,
                'sprint' => $selectedSprint,
                'sprint_key' => $selectedSprint['key']
            ]
        ]);

    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionError()
    {
        return $this->render('index');
    }

}
