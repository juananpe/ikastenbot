<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 17/02/19
 * Time: 11:26.
 */

namespace App\Service;

use App\Exception\NoStrategySetException;

class StringComparator
{
    const STRATEGY_TOKENS = 'strategy_tokens';
    const STRATEGY_N_GRAMS = 'strategy_n_grams';
    const STRATEGY_N_SHINGLES = 'strategy_n_shingles';

    /**
     * @var string
     */
    private $currentStrategy;

    /**
     * @var int
     */
    private $n;

    /**
     * @return string The string identifier for the string-chunking strategy currently set
     */
    public function getCurrentStrategy(): string
    {
        return $this->currentStrategy;
    }

    /**
     * @return int The parameter n set for the current strategy or null if not applicable
     */
    public function getN(): int
    {
        return $this->n;
    }

    /**
     * Set the string-chunking strategy to tokens.
     *
     * After having all of their whitespaces, tabs, etc. unified into single whitespaces,
     * Strings will be divided into tokens: substrings separated by whitespaces.
     *
     * e.g.: "ab     cd efg   hi j" -> ["ab", "cd", "efg", "hi", "j"]
     */
    public function setStrategyTokens()
    {
        $this->currentStrategy = self::STRATEGY_TOKENS;
        $this->n = null;
    }

    /**
     * Set the string-chunking strategy to n-grams with n equals $n.
     *
     * After having all of their whitespaces, tabs, etc. removed,
     * Strings will be divided into n-grams: substrings of n adjacent characters.
     *
     * e.g.: "ab     cd efg   hi j" -> (n = 3) ["abc", "def", "ghi", "j"]
     *
     * @param int $n Value for the parameter n
     */
    public function setStrategyNGrams($n)
    {
        $this->currentStrategy = self::STRATEGY_N_GRAMS;
        $this->n = $n;
    }

    /**
     * Set the string-chunking strategy to n-shingles with n equals $n.
     *
     * After having all of their whitespaces, tabs, etc. unified into single whitespaces,
     * Strings will be divided into n-shingles: substrings formed by n adjacent tokens
     * joined by whitespaces.
     *
     * e.g.: "ab     cd efg   hi j" -> (n = 2) ["ab cd", "efg hi", "j"]
     *
     * @param int $n Value for the parameter n
     */
    public function setStrategyNShingles($n)
    {
        $this->currentStrategy = self::STRATEGY_N_SHINGLES;
        $this->n = $n;
    }

    /**
     * Removes the first appearance of a substring formed by a series of numbers
     * separated by dots (i.e.: '1', '2.3.', '1.2.4', etc.).
     *
     * @param string $string The string to be processed
     *
     * @return string String resulting from the process
     */
    public function removeNumbersAtStart($string): string
    {
        return preg_replace('/^([1-9]+(\.)*)+\s*/', '', $string, 1);
    }

    /**
     * Returns the similarity index between two string using the
     * Optimal Alignment edit distance.
     *
     * @param string $s1 First string
     * @param string $s2 Second string
     *
     * @return float Similarity index between the strings
     */
    public function similarityOA($s1, $s2): float
    {
        $cleanS1 = $this->cleanString($s1);
        $cleanS2 = $this->cleanString($s2);

        // calculate the Optimal Alignment edit distance between the two strings
        $distance = $this->editDistance($cleanS1, $cleanS2, false, false);

        return $this->similarityEdit($cleanS1, $cleanS2, $distance);
    }

    /**
     * Returns the similarity index between two string using the
     * Levenshtein edit distance.
     *
     * @param string $s1 First string
     * @param string $s2 Second string
     *
     * @return float Similarity index between the strings
     */
    public function similarityLevenshtein($s1, $s2): float
    {
        $cleanS1 = $this->cleanString($s1);
        $cleanS2 = $this->cleanString($s2);

        // calculate the Levenshtein edit distance between the two strings
        $distance = $this->editDistance($cleanS1, $cleanS2, true, false);

        return $this->similarityEdit($cleanS1, $cleanS2, $distance);
    }

    /**
     * Returns the similarity index between two string using the
     * Damerau-Levenshtein edit distance.
     *
     * @param string $s1 First string
     * @param string $s2 Second string
     *
     * @return float Similarity index between the strings
     */
    public function similarityDamLev($s1, $s2): float
    {
        $cleanS1 = $this->cleanString($s1);
        $cleanS2 = $this->cleanString($s2);

        // calculatethe Damerau-Levenshtein edit distance between the two strings
        $distance = $this->editDistance($cleanS1, $cleanS2, true, true);

        return $this->similarityEdit($cleanS1, $cleanS2, $distance);
    }

    /**
     * Returns the similarity index between two string using the
     * Jaccard index and the string chunking strategy set via
     * the setStrategy*() methods.
     *
     * @param string $s1 First string
     * @param string $s2 Second string
     *
     * @throws NoStrategySetException
     *
     * @return float Similarity index between the strings
     */
    public function similarityJaccard($s1, $s2): float
    {
        $cleanS1 = $this->cleanString($s1);
        $cleanS2 = $this->cleanString($s2);

        $set1 = $this->chunkString($cleanS1);
        $set2 = $this->chunkString($cleanS2);

        return $this->jaccardIndex($set1, $set2);
    }

    /**
     * Returns the similarity index between two string using the
     * Dice index and the string chunking strategy set via
     * the setStrategy*() methods.
     *
     * @param string $s1 First string
     * @param string $s2 Second string
     *
     * @throws NoStrategySetException
     *
     * @return float Similarity index between the strings
     */
    public function similarityDice($s1, $s2): float
    {
        $cleanS1 = $this->cleanString($s1);
        $cleanS2 = $this->cleanString($s2);

        $set1 = $this->chunkString($cleanS1);
        $set2 = $this->chunkString($cleanS2);

        return $this->diceIndex($set1, $set2);
    }

    /**
     * Returns the similarity index between two string using the
     * Overlap index and the string chunking strategy set via
     * the setStrategy*() methods.
     *
     * @param string $s1 First string
     * @param string $s2 Second string
     *
     * @throws NoStrategySetException
     *
     * @return float Similarity index between the strings
     */
    public function similarityOverlap($s1, $s2): float
    {
        $cleanS1 = $this->cleanString($s1);
        $cleanS2 = $this->cleanString($s2);

        $set1 = $this->chunkString($cleanS1);
        $set2 = $this->chunkString($cleanS2);

        return $this->OverlapIndex($set1, $set2);
    }

    /**
     * Obtains a similarity metric based on the Manhattan distance
     * between the strings.
     * Both string are represented as vectors of the space
     * formed by all the substring obtained by chunking the strings.
     * The Manhattan distance is obtained from said vectors.
     * The result is 1 minus their actual Manhattan distance over their maximum
     * possible Manhattan distance.
     *
     * @param string $s1 First string
     * @param string $s2 Second string
     *
     * @throws NoStrategySetException
     *
     * @return float Manhattan similarity between the strings
     */
    public function similarityManhattan($s1, $s2): float
    {
        $cleanS1 = $this->cleanString($s1);
        $cleanS2 = $this->cleanString($s2);

        // allow duplicates when chunking the strings
        $set1 = $this->chunkString($cleanS1, true);
        $set2 = $this->chunkString($cleanS2, true);

        // obtain all the substring without duplicates
        $values = array_unique(array_merge($set1, $set2));

        // square sum of the elements of the difference vector
        $squareSum = 0;

        // square sum of the elements of the sum vector (maximum distance)
        $maxSum = 0;

        foreach ($values as $value) {
            /*
             * for each substring (dimension), the value of each word
             * for said dimension is equal to how many times the substring
             * appears in them
             */

            // string1's value for the current dimension
            $value1 = 0;
            for ($i = 0; $i < count($set1); ++$i) {
                if ($set1[$i] === $value) {
                    ++$value1;
                }
            }

            // string2's value for the current dimension
            $value2 = 0;
            for ($i = 0; $i < count($set2); ++$i) {
                if ($set2[$i] === $value) {
                    ++$value2;
                }
            }

            // add the square difference to the cumulative sum
            $squareSum += pow($value2 - $value1, 2);

            // add the square sum to the cumulative sum
            $maxSum += pow($value2 + $value1, 2);
        }

        return 1 - sqrt($squareSum) / sqrt($maxSum);
    }

    /**
     * Unify all the whitespace characters (spaces, tabs, etc.) in the string
     * by turning them into a single whitespace. Also removes leading and
     * trailing whitespaces.
     *
     * @param string $string The string to be processed
     *
     * @return string String resulting from the process
     */
    private function unifyWhitespaces($string): string
    {
        return preg_replace('/\s+/', ' ', trim($string));
    }

    /**
     * Remove all the whitespace characters (spaces, tabs, etc.) in the string.
     *
     * @param string $string The string to be processed
     *
     * @return string String resulting from the process
     */
    private function removeWhitespaces($string): string
    {
        return preg_replace('/\s+/', '', $string);
    }

    /**
     * Preprocess a string to remove noise for the comparison.
     * Transforms the string to lowercase, removes non-alphanumeric characters
     * (comas, periods, etc.) and removes articles and other words
     * that don't carry important information.
     *
     * @param string $string The string to be processed
     *
     * @return string String resulting from the process
     */
    private function cleanString($string): string
    {
        // transform to lowercase
        $loweCase = strtolower($string);

        // remove non-alphanumeric characters
        $alphanumeric = preg_replace('/[^\w\sá-úÁ-úñÑ]/', '', $loweCase);

        // remove articles and other common words that don't add information
        // TODO: completar para euskera
        $tokens = ['el', 'la', 'los', 'las', 'de', 'del', 'con', 'sin', 'a', 'y', 'e', 'o', 'u', 'que', 'qué', 'de', 'en',
            'entre', 'para', 'por', 'según', 'sin', 'sobre', 'mediante', ];

        $cleanString = $alphanumeric;
        foreach ($tokens as $token) {
            $cleanString = preg_replace('/[^\wsá-úÁ-úñÑ]'.$token.'\s/i', ' ', $cleanString);
        }

        return $cleanString;
    }

    /**
     * Separates a string into tokens (words separated by whitespaces).
     *
     * @param string $string          The string to tokenize
     * @param bool   $allowDuplicates Flag to allow/disallow duplicate chunks
     *
     * @return array Array of tokens obtained from the string
     */
    private function chunkToTokens($string, $allowDuplicates = false): array
    {
        $clean_string = $this->unifyWhitespaces($string);

        if ($allowDuplicates) {
            return explode(' ', $clean_string);
        }

        return array_unique(explode(' ', $clean_string));
    }

    /**
     * Separates a string into n-grams. An n-gram is a substring of n consecutive
     * characters, ignoring whitespaces.
     *
     * @param string $string          The string to divide in n-grams
     * @param int    $n               The number of characters of each n-gram
     * @param bool   $allowDuplicates Flag to allow/disallow duplicate chunks
     *
     * @return array Array of n-grams obtained from the string
     */
    private function chunkToNGrams($string, $n, $allowDuplicates = false): array
    {
        $clean_string = $this->removeWhitespaces($string);

        if ($allowDuplicates) {
            return $this->mb_split($clean_string, $n);
        }

        return array_unique($this->mb_split($clean_string, $n));
    }

    /**
     * Separates a string into n-shingles. An n-shingle is a substring of n consecutive
     * tokens separated by whitespaces.
     *
     * @param string $string          The string to divide in n-shingles
     * @param int    $n               The number of tokens of each n-shingle
     * @param bool   $allowDuplicates Flag to allow/disallow duplicate chunks
     *
     * @return array Array of n-shingles obtained from the string
     */
    private function chunkToNShingles($string, $n, $allowDuplicates = false): array
    {
        $clean_string = $this->unifyWhitespaces($string);
        $tokens = explode(' ', $clean_string);
        $shingles = [];
        $indexTokens = 0;
        $indexShingles = 0;
        while ($indexTokens < sizeof($tokens)) {
            if (array_key_exists($indexShingles, $shingles)) {
                $shingles[$indexShingles] .= ' '.$tokens[$indexTokens];
            } else {
                array_push($shingles, $tokens[$indexTokens]);
            }
            ++$indexTokens;
            $indexShingles = intdiv($indexTokens, $n);
        }

        if ($allowDuplicates) {
            return $shingles;
        }

        return array_unique($shingles);
    }

    /**
     * Separates the string into substrings using the strategy set by the
     * setStrategy*() methods.
     *
     * @param string $string          The string to be divided
     * @param bool   $allowDuplicates Flag to allow/disallow duplicate chunks
     *
     * @throws NoStrategySetException
     *
     * @return array Array containing the substrings
     */
    private function chunkString($string, $allowDuplicates = false): array
    {
        switch ($this->currentStrategy) {
            case self::STRATEGY_TOKENS:
                return $this->chunkToTokens($string, $allowDuplicates);
            case self::STRATEGY_N_GRAMS:
                return $this->chunkToNGrams($string, $this->n, $allowDuplicates);
            case self::STRATEGY_N_SHINGLES:
                return $this->chunkToNShingles($string, $this->n, $allowDuplicates);
            default:
                throw new NoStrategySetException();
        }
    }

    /**
     * Splits a given string into an array of substring of the
     * specified size.
     *
     * @param string $string String to split
     * @param int    $size   Length of each substring
     *
     * @return array Array with the substrings obtained from the split
     */
    private function mb_split($string, $size): array
    {
        $res = [];
        $length = mb_strlen($string, 'utf-8');
        $index = 0;
        for ($i = 0; $i < $length; $i += $size) {
            $res[$index] = mb_substr($string, $i, $size, 'utf-8');
            ++$index;
        }

        return $res;
    }

    /**
     * Calculates a similarity index (min 0, max 1) for the strings $s1 and $s2
     * given their edit distance to each other.
     *
     * @param string $s1       First string
     * @param string $s2       Second string
     * @param int    $distance Edit distance between the strings
     *
     * @return float Similarity index between the strings
     */
    private function similarityEdit($s1, $s2, $distance): float
    {
        $maxLen = max(strlen($s1), strlen($s2));

        return ($maxLen - $distance) / $maxLen;
    }

    /**
     * Returns the edit distance between two string given a set of possible operations.
     * Insertion and deletion of characters are always allowed.
     * Character substitution and transposition of adjacent characters depend
     * on the parameters.
     *
     * @param string $s1            First string
     * @param string $s2            Second string
     * @param bool   $substitutions Allow/Disallow substitution of characters
     * @param bool   $transposition Allow/Disallow transposition of adjacent characters
     *
     * @return int edit distance between the strings using the permitted operations
     */
    private function editDistance($s1, $s2, $substitutions, $transposition): int
    {
        $dMatrix = [];

        // initialize first row, corresponding to the first word
        for ($col = 0; $col <= strlen($s1); ++$col) {
            $dMatrix[0][$col] = $col;
        }

        // initialize first column, corresponding to the second word
        for ($row = 0; $row <= strlen($s2); ++$row) {
            $dMatrix[$row][0] = $row;
        }

        // traverse matrix
        for ($row = 1; $row <= strlen($s2); ++$row) {
            for ($col = 1; $col <= strlen($s1); ++$col) {
                if ($s1[$col - 1] === $s2[$row - 1]) {
                    // if the letters coincide, keep previous value
                    // no operation needed
                    $dMatrix[$row][$col] = $dMatrix[$row - 1][$col - 1];
                } else {
                    // otherwise, obtain value of insertion or deletion
                    $min = min($dMatrix[$row - 1][$col] + 1, $dMatrix[$row][$col - 1] + 1);
                    if ($substitutions) {
                        // take substitution into account if necessary
                        $min = min($min, $dMatrix[$row - 1][$col - 1] + 1);
                    }
                    if ($transposition) {
                        // take transposition into account if necessary
                        if ($row > 1 and $col > 1 and
                            $s1[$col - 1] === $s2[$row - 2] and
                            $s1[$col - 2] === $s2[$row - 1]) {
                            $min = min($min, $dMatrix[$row - 2][$col - 2] + 1);
                        }
                    }
                    $dMatrix[$row][$col] = $min;
                }
            }
        }

        return $dMatrix[strlen($s2)][strlen($s1)];
    }

    /**
     * Returns the value of the Jaccard index for two given sets
     * in the form of arrays.
     *
     * @param array $set1 First set
     * @param array $set2 Second set
     *
     * @return float Jaccard index
     */
    private function jaccardIndex($set1, $set2): float
    {
        $setUnion = array_unique(array_merge($set1, $set2));

        return count(array_intersect($set1, $set2)) / count($setUnion);
    }

    /**
     * Returns the value of the Dice index for two given sets
     * in the form of arrays.
     *
     * @param array $set1 First set
     * @param array $set2 Second set
     *
     * @return float Dice index
     */
    private function diceIndex($set1, $set2): float
    {
        return 2 * count(array_intersect($set1, $set2)) / (count($set1) + count($set2));
    }

    /**
     * Returns the value of the Overlap index for two given sets
     * in the form of arrays.
     *
     * @param array $set1 First set
     * @param array $set2 Second set
     *
     * @return float Overlap index
     */
    private function overlapIndex($set1, $set2): float
    {
        return count(array_intersect($set1, $set2)) / min(count($set1), count($set2));
    }
}
