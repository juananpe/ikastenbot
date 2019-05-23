<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Task;
use App\Exception\NoStrategySetException;
use Doctrine\ORM\EntityManager;

class SimilarTaskFinder
{
    /**
     * @var StringComparator
     */
    private $sc;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * SimilarTaskFinder constructor.
     *
     * @param StringComparator $sc
     * @param EntityManager    $em
     */
    public function __construct(StringComparator $sc, EntityManager $em)
    {
        $this->sc = $sc;

        $this->em = $em;
    }

    /**
     * Returns a list of all the tasks with atypical duration when
     * compared to similarly named tasks from the database.
     * The list contains the task's ID ('taskId'), name ('taskName'),
     * duration ('taskDuration') and the average duration of the
     * similar tasks ('avgDuration').
     *
     * @param array $targetTasks List of tasks to analyze
     *
     * @return array Result list
     */
    public function getTasksWithAtypicalDuration(array $targetTasks): array
    {
        $percentBuffer = 0.2; // 20% buffer

        $result = [];

        $dbTasks = $this->em->getRepository(Task::class)->findAll();
        $accTimes = $this->getSimilarTasksDurations($targetTasks, $dbTasks);
        foreach ($accTimes as $targetInfo) {
            // if similar tasks have been found
            if (0 !== $targetInfo['similarTasksCount']) {
                $targetDuration = $targetInfo['taskDuration'];
                $avgDuration = $targetInfo['similarTasksAccDur'] / $targetInfo['similarTasksCount'];

                $upperBound = $avgDuration * (1 + $percentBuffer);
                $lowerBound = $avgDuration * (1 - $percentBuffer);

                // if the task duration is outside the range
                if ($targetDuration < $lowerBound || $targetDuration > $upperBound) {
                    $result[] = [
                        'taskName' => $targetInfo['taskName'],
                        'taskId' => $targetInfo['taskId'],
                        'taskDuration' => $targetInfo['taskDuration'],
                        'avgDuration' => $avgDuration,
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Returns a list containing, for each target task, how many
     * tasks from the other list have similar names and the combined
     * duration of said similar tasks.
     *
     * @param array $targetTasks List of tasks to analyze
     * @param array $taskList    List of tasks to compare with each target task
     *
     * @return array Result list
     */
    private function getSimilarTasksDurations(array $targetTasks, array $taskList): array
    {
        $result = [];
        foreach ($targetTasks as $target) {
            $targetInfo = [
                'taskName' => $target->getName(),
                'taskId' => $target->getId(),
                'taskDuration' => $target->getDuration(),
                'similarTasksAccDur' => 0,
                'similarTasksCount' => 0,
            ];

            foreach ($taskList as $task) {
                if (($task->getChat_id() === null ||
                    $task->getChat_id() !== $target->getChat_id()) &&
                    $this->areSimilar($target->getName(), $task->getName())) {
                    /*
                     * if task and target come from different users
                     * and have similar names...
                     *
                     * This is to avoid a task influencing itself or other
                     * tasks from the same Gantt.
                     */
                    $targetInfo['similarTasksAccDur'] += $task->getDuration();
                    ++$targetInfo['similarTasksCount'];
                }
            }

            $result[] = $targetInfo;
        }

        return $result;
    }

    /**
     * Computes whether two strings are similar or not.
     *
     * @param string $taskName1 First string
     * @param string $taskName2 Second string
     *
     * @return bool True if the strings are considered similar, False otherwise
     */
    private function areSimilar(string $taskName1, string $taskName2): bool
    {
        //TODO: probably needs tuning

        if (0.6 <= $this->sc->similarityDamLev($taskName1, $taskName2)) {
            return true;
        }

        try {
            $this->sc->setStrategyTokens();
            if (1 === $this->sc->similarityOverlap($taskName1, $taskName2)) {
                return true;
            }

            if (0.5 <= $this->sc->similarityManhattan($taskName1, $taskName2)) {
                return true;
            }

            if (0.5 <= $this->sc->similarityDice($taskName1, $taskName2)) {
                return true;
            }

            $this->sc->setStrategyNGrams(2);
            if (0.6 <= $this->sc->similarityManhattan($taskName1, $taskName2)) {
                return true;
            }

            if (0.7 <= $this->sc->similarityDice($taskName1, $taskName2)) {
                return true;
            }

            $this->sc->setStrategyNGrams(3);
            if (0.6 <= $this->sc->similarityManhattan($taskName1, $taskName2)) {
                return true;
            }

            if (0.7 <= $this->sc->similarityDice($taskName1, $taskName2)) {
                return true;
            }
        } catch (NoStrategySetException $e) {
        }

        return false;
    }
}
