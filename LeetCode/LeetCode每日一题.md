#### [03-26 999. 车的可用捕获量](https://leetcode-cn.com/problems/available-captures-for-rook/)

```php
class Solution {

    /**
     * @param String[][] $board
     * @return Integer
     */
    function numRookCaptures($board) {
        $cnt = $st = $ed = 0;
 		//查找车
        for ($i =0 ; $i < 8; $i++) {
            for ($j = 0; $j < 8; $j++) {
                if ($board[$i][$j] == 'R') {
                    $st = $i;
                    $ed = $j;
                    break;
                }
            }
        }
        //遍历扩散
        $dx = [-1, 1, 0, 0];
        $dy = [0, 0, -1, 1];
        for ($i = 0; $i < 4; $i++) {
            for ($step = 0;; $step++){
                $x = $st + $dx[$i] * $step;
                $y = $ed + $dy[$i] * $step;
                //遇到边界或者白色的象
                if ($x < 0 || $x >= 8 || $y < 0 || $y >= 8 || $board[$x][$y] =='B') break;
                if ($board[$x][$y] == 'p') {
                    $borad[$x][$y] = '.';
                    $cnt++;
                    break;
                } 
            }
        }
        
        return $cnt;
    }
}
```

#### [03-27 914. 卡牌分组](https://leetcode-cn.com/problems/x-of-a-kind-in-a-deck-of-cards/)

```php
class Solution {

    /**
     * @param Integer[] $deck
     * @return Boolean
     */
    function hasGroupsSizeX($deck) {
        $map = [];
        foreach ($deck as $n) {
            $map[$n] = $map[$n]? $map[$n] + 1: 1;
        }
        $g = -1;
        foreach($map as $i=>$count) {
            if ($g == -1) {
                $g = $count;
            } else {
                $g = $this->gcd($g, $count);
            }
        }
        return $g >= 2;
    }
    function hasGroupsSizeX2($deck) {
        $map = array_count_values($deck);
        $g = -1;
        foreach($map as $i=>$count) {
            $g = $g == -1 ? $count : $this->gcd($g, $count);
        }
        return $g >= 2;
    }
    function gcd ($x , $y) {
        return $y == 0 ? $x : $this->gcd($y, $x % $y);
    }
}
```

#### [03-29 1162. 地图分析](https://leetcode-cn.com/problems/as-far-from-land-as-possible/)

```php
class Solution {

    private $n;
    private $grid;
    /**
     * 广度优先搜索
     * @param Integer[][] $grid
     * @return Integer
     */
    function maxDistance($grid) {
        $n = count($grid);
        $q = [];
        for ($i = 0; $i < $n; $i++) 
            for ($j = 0; $j < $n; $j++) 
                if ($grid[$i][$j] == 1) 
                    $q[] = [$i, $j];
        if (count($q) == $n * $n || count($q) ==0) return -1;
        $level = 0;
        $dx = [1, -1, 0, 0];
        $dy = [0, 0, 1, -1];
        while($q) {
            $size = count($q);
            while ($size--) {
                [$x, $y] = array_shift($q);
                for ($i = 0; $i < 4; $i++) {
                    $xi = $x + $dx[$i];
                    $yi = $y + $dy[$i];
                    if ($xi >= 0 && $xi < $n && $yi >= 0 && $yi < $n && $grid[$xi][$yi] == 0){
                        $q[] = [$xi, $yi];
                        $grid[$xi][$yi] = 1;
                    }
                }
            }
            $level += 1;
        }
        return $level - 1;
    }
    
    /**
     * 动态规划
     * @param Integer[][] $grid
     * @return Integer
     */
    function maxDistance($grid) {
        $n = count($grid);
        $m = count($grid[0]);
        $res = 0;
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $m; $j++) {
                if ($grid[$i][$j] == 1) continue;
                $grid[$i][$j] = 201; //201 here cuz as the despription, the size won't exceed 100.
                if ($i > 0) $grid[$i][$j] = min($grid[$i][$j], $grid[$i-1][$j] + 1);
                if ($j > 0) $grid[$i][$j] = min($grid[$i][$j], $grid[$i][$j-1] + 1);
            }
        }
        
        for ($i = $n-1; $i > -1; $i--) {
            for ($j = $m-1; $j > -1; $j--) {
                if ($grid[$i][$j] == 1) continue;
                if ($i < $n-1) $grid[$i][$j] = min($grid[$i][$j], $grid[$i+1][$j] + 1);
                if ($j < $m-1) $grid[$i][$j] = min($grid[$i][$j], $grid[$i][$j+1] + 1);
                $res = max($res, $grid[$i][$j]); //update the maximum
            }
        }
        return $res == 201 ? -1 : $res - 1;
    }
}
```

#### [03-30 面试题62. 圆圈中最后剩下的数字](https://leetcode-cn.com/problems/yuan-quan-zhong-zui-hou-sheng-xia-de-shu-zi-lcof/)

```php
class Solution {

    /**
     * 递归
     * @param Integer $n
     * @param Integer $m
     * @return Integer
     */
    function lastRemaining($n, $m) {
        if ($n == 1)
            return 0;
        $x = $this->lastRemaining($n - 1, $m);
        return ($m + $x) % $n;
    }
}
```



#### [04-01 1111. 有效括号的嵌套深度](https://leetcode-cn.com/problems/maximum-nesting-depth-of-two-valid-parentheses-strings/)

```php
class Solution {

    /**
     * @param String $seq
     * @return Integer[]
     */
    function maxDepthAfterSplit($seq) {
        $ans = [];
        $d = 0;
        foreach (str_split($seq) as  $c) {
            if ($c == '(') {
                $d += 1;
                $ans[] = $d % 2;
            } else {
                $ans[] = $d % 2;
                $d -= 1;
            }
        }
        return $ans;
    }
}
```

#### [04-14 445. 两数相加 II](https://leetcode-cn.com/problems/add-two-numbers-ii/)

```php
/**
 * Definition for a singly-linked list.
 * class ListNode {
 *     public $val = 0;
 *     public $next = null;
 *     function __construct($val) { $this->val = $val; }
 * }
 */
class Solution {

    /**
     * 方法一：栈
     * 方法二：链表翻转相加
     * @param ListNode $l1
     * @param ListNode $l2
     * @return ListNode
     */
    function addTwoNumbers($l1, $l2) {
        if (!$l1) return $l2;
        if (!$l2) return $l1;
        $s1 = $s2 = [];//数组模拟栈
        while ($l1) {
            $s1[] = $l1->val;
            $l1 = $l1->next;
        }
        while ($l2) {
            $s2[] = $l2->val;
            $l2 = $l2->next;
        }
        $sum = 0;
        $head = null;
        while ($s1 || $s2 || $sum > 0) {
            $sum += $s1 ? array_pop($s1) : 0;
            $sum += $s2 ? array_pop($s2) : 0;
            $node = new ListNode($sum % 10);
            $node->next = $head;
            $head = $node;
            $sum = intval($sum / 10);
        }
        return $head;
    }
}
```

#### [04-15 542. 01 矩阵](https://leetcode-cn.com/problems/01-matrix/)

[dp解法](https://leetcode.com/problems/01-matrix/discuss/101051/Simple-Java-solution-beat-99-(use-DP))

```php
class Solution {

    /**
     * @param Integer[][] $matrix
     * @return Integer[][]
     */
    function updateMatrix($matrix) {
        $m = count($matrix);
        if ($m == 0) return $matrix;
        $n = count($matrix[0]);
        $ans = [];
        $queue = [];
        for ($i = 0; $i < $m; $i++) {
            for ($j = 0; $j < $n; $j++) {
                if ($matrix[$i][$j] == 0) {
                    $ans[$i][$j] = 0;
                    $queue[] = [$i, $j];
                } else {
                    $ans[$i][$j] = $m + $n; //最大值
                }
            }
        }
        $dx = [0, 1, 0, -1];
        $dy = [1, 0, -1, 0];
        while ($queue) {
            [$x, $y] = array_shift($queue);
            for($i = 0; $i < 4; $i++){
                $nx = $x + $dx[$i];
                $ny = $y + $dy[$i];
                if($nx >= 0 && $nx < $m && $ny >= 0 && $y < $n){
                    if($ans[$nx][$ny] > $ans[$x][$y] + 1){
                        $ans[$nx][$ny] = $ans[$x][$y] + 1;
                        $queue[] = [$nx, $ny];
                    }
                }
            }
        }
        return $ans;
    }
}
```

#### [04-16 56. 合并区间](https://leetcode-cn.com/problems/merge-intervals/)

```php
class Solution {

    /**
     * @param Integer[][] $intervals
     * @return Integer[][]
     */
    function merge($intervals) {
        $count = count($intervals);
        $output = [];
        array_multisort(array_column($intervals, 0),$intervals);
        for ($i = 0, $j = 0; $i < $count; $i++) {
            if (empty($output) || $output[$j - 1][1] < $intervals[$i][0]) {
                $output[$j++] = $intervals[$i];
            } else {
                $output[$j - 1][1] = max($intervals[$i][1], $output[$j - 1][1]);
            } 
        }
        return $output;
    }
}
```

#### [04-17 55. 跳跃游戏](https://leetcode-cn.com/problems/jump-game/)

```php
class Solution {
    /**
     * 方法一：贪心法
     * 方法二：动态规划
     * @param Integer[] $nums
     * @return Boolean
     */
    function canJump($nums) {
        if (!$nums) return false;
        $revCanJump = count($nums) - 1;
        for ($i = $revCanJump; $i >= 0; $i--) {
            if ($nums[$i] + $i >= $revCanJump) {
                $revCanJump = $i;
            }
        }
        return $revCanJump == 0;
    }
}
```

#### [04-21 1248. 统计「优美子数组」](https://leetcode-cn.com/problems/count-number-of-nice-subarrays/)

```php
class Solution {

    /**
     * @param Integer[] $nums
     * @param Integer $k
     * @return Integer
     */
    function numberOfSubarrays($nums, $k) {
		$n = count($nums);
        $odd = [];
        $ans = $cnt = 0;
        $odd[0] = -1; 
        for ($i = 0; $i < $n; $i++) {
            if ($nums[$i] & 1) $odd[++$cnt] = $i;
        }
        $odd[++$cnt] = $n;
        for ($i = 1; $i + $k <= $cnt; $i++) {
            $ans + ($odd[$i] - $odd[$i - 1]) * ($odd[$i + $k] - $odd[$i + $k -1]);
        }
        return $ans;
    }
    
     function numberOfSubarrays($nums, $k) {
		$n = count($nums);
        $ans = $odd = 0;
        $cnt = [1];
        for ($i = 0; $i < $n; $i++) {
            $odd += $nums[$i] & 1;
            $ans += $odd > $k ? $cnt[$odd - $k] : 0;
           	$cnt[$odd] += 1;
        }
        return $ans;
    }
}
```