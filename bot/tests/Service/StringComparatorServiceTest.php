<?php

namespace App\Tests\Service;

use App\Service\StringComparator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Service\StringComparator
 *
 * @internal
 */
class StringComparatorServiceTest extends TestCase
{
    /**
     * String comparator.
     *
     * @var StringComparator
     */
    private $sc;

    public function setUp()
    {
        $this->sc = new StringComparator();
    }

    /**
     * @covers \App\Service\StringComparator::getCurrentStrategy()
     * @covers \App\Service\StringComparator::getN()
     * @covers \App\Service\StringComparator::setStrategyNGrams()
     * @covers \App\Service\StringComparator::setStrategyNShingles()
     * @covers \App\Service\StringComparator::setStrategyTokens()
     */
    public function testSetStrategy()
    {
        $this->sc->setStrategyTokens();
        $this->assertEquals(StringComparator::STRATEGY_TOKENS, $this->sc->getCurrentStrategy());

        $nGrams = 4;
        $this->sc->setStrategyNGrams($nGrams);
        $this->assertEquals(StringComparator::STRATEGY_N_GRAMS, $this->sc->getCurrentStrategy());
        $this->assertEquals($nGrams, $this->sc->getN());

        $nShingles = 4;
        $this->sc->setStrategyNShingles($nShingles);
        $this->assertEquals(StringComparator::STRATEGY_N_SHINGLES, $this->sc->getCurrentStrategy());
        $this->assertEquals($nShingles, $this->sc->getN());
    }

    /**
     * @covers \App\Service\StringComparator::removeNumbersAtStart()
     */
    public function testRemoveNumbersAtStart()
    {
        $s0 = 'Foo Bar';
        $s1 = '1. '.$s0;
        $s2 = '1.2'.$s0;
        $s3 = '19.22.3.'.$s0;

        $this->assertEquals($s0, $this->sc->removeNumbersAtStart($s1));
        $this->assertEquals($s0, $this->sc->removeNumbersAtStart($s2));
        $this->assertEquals($s0, $this->sc->removeNumbersAtStart($s3));
    }

    /**
     * @covers \App\Service\StringComparator::similarityOA()
     */
    public function testSimilarityOA()
    {
        $s1 = 'Saturday';
        $s2 = 'Sunday';
        $distance = 4;
        $similarity = (strlen($s1) - $distance) / strlen($s1);

        $this->assertEquals($similarity, $this->sc->similarityOA($s1, $s2));
    }

    /**
     * @covers \App\Service\StringComparator::similarityLevenshtein()
     */
    public function testSimilarityLevenshtein()
    {
        $s1 = 'Saturday';
        $s2 = 'Sunday';
        $distance = 3;
        $similarity = (strlen($s1) - $distance) / strlen($s1);

        $this->assertEquals($similarity, $this->sc->similarityLevenshtein($s1, $s2));
    }

    /**
     * @covers \App\Service\StringComparator::similarityDamLev()
     */
    public function testSimilarityDamLev()
    {
        $s1 = 'Sitetn';
        $s2 = 'Kitten';
        $distance = 2;
        $similarity = (strlen($s1) - $distance) / strlen($s1);

        $this->assertEquals($similarity, $this->sc->similarityDamLev($s1, $s2));
    }

    /**
     * @covers \App\Service\StringComparator::similarityJaccard()
     */
    public function testSimilarityJaccard()
    {
        $this->sc->setStrategyTokens();
        $s1 = 'foo bar eggs';
        $s2 = 'bar beep boop';
        $similarity = 1 / 5;

        $this->assertEquals($similarity, $this->sc->similarityJaccard($s1, $s2));

        $this->sc->setStrategyNGrams(3);
        $s1 = 'foo bar eggs';  //[foo, bar, egg, s]
        $s2 = 'bar eggp boop'; //[bar, egg, pbo, op]
        $similarity = 2 / 6;

        $this->assertEquals($similarity, $this->sc->similarityJaccard($s1, $s2));

        $this->sc->setStrategyNShingles(2);
        $s1 = 'foo bar eggs';  //[foo bar, eggs]
        $s2 = 'bar eggp eggs'; //[bar eggp, eggs]
        $similarity = 1 / 3;

        $this->assertEquals($similarity, $this->sc->similarityJaccard($s1, $s2));
    }

    /**
     * @covers \App\Service\StringComparator::similarityDice()
     */
    public function testSimilarityDice()
    {
        $this->sc->setStrategyTokens();
        $s1 = 'foo bar eggs';
        $s2 = 'bar beep boop';
        $similarity = 2 / 6;

        $this->assertEquals($similarity, $this->sc->similarityDice($s1, $s2));

        $this->sc->setStrategyNGrams(3);
        $s1 = 'foo bar eggs';  //[foo, bar, egg, s]
        $s2 = 'bar eggp boop beep'; //[bar, egg, pbo, opb, eep]
        $similarity = 4 / 9;

        $this->assertEquals($similarity, $this->sc->similarityDice($s1, $s2));

        $this->sc->setStrategyNShingles(2);
        $s1 = 'foo bar eggs doop fblthp';  //[foo bar, eggs doop, fblthp]
        $s2 = 'bar eggp eggs doop'; //[bar eggp, eggs doop]
        $similarity = 2 / 5;

        $this->assertEquals($similarity, $this->sc->similarityDice($s1, $s2));
    }

    /**
     * @covers \App\Service\StringComparator::similarityDice()
     */
    public function testSimilarityOverlap()
    {
        $this->sc->setStrategyTokens();
        $s1 = 'foo bar eggs';
        $s2 = 'bar beep boop';
        $similarity = 1 / 3;

        $this->assertEquals($similarity, $this->sc->similarityOverlap($s1, $s2));

        $this->sc->setStrategyNGrams(3);
        $s1 = 'foo bar eggs';  //[foo, bar, egg, s]
        $s2 = 'bar eggp boop beep'; //[bar, egg, pbo, opb, eep]
        $similarity = 2 / 4;

        $this->assertEquals($similarity, $this->sc->similarityOverlap($s1, $s2));

        $this->sc->setStrategyNShingles(2);
        $s1 = 'foo bar eggs doop fblthp';  //[foo bar, eggs doop, fblthp]
        $s2 = 'bar eggp eggs doop'; //[bar eggp, eggs doop]
        $similarity = 1 / 2;

        $this->assertEquals($similarity, $this->sc->similarityOverlap($s1, $s2));
    }
}
