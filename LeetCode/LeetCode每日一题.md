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

