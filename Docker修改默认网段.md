#### [(03-25) 892. 三维形体的表面积](https://leetcode-cn.com/problems/surface-area-of-3d-shapes/)

```php
class Solution {

    /**
     * @param Integer[][] $grid
     * @return Integer
     */
    function surfaceArea($grid) {
        $n = count($grid);
        $area = 0;
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $level = $grid[$i][$j];
                if ($level > 0) {
                    //贡献的面积 << 2 相当于 * 4
                    $area += 2 + ($level << 2);
                    //减去重合的面积 << 1 相当于 * 2
                    $area -= $i > 0 ? min($level, $grid[$i - 1][$j]) << 1 : 0;
                    //减去重合的面积
                    $area -= $j > 0 ? min($level, $grid[$i][$j - 1]) << 1 : 0;
                }
            }
        }
        return $area;
    }
}
```

