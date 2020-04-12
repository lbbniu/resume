### 数组和链表

#### [206. 反转链表](https://leetcode-cn.com/problems/reverse-linked-list/)

#### [24. 两两交换链表中的节点](https://leetcode-cn.com/problems/swap-nodes-in-pairs/)

#### [141. 环形链表](https://leetcode-cn.com/problems/linked-list-cycle/)

#### [142. 环形链表 II](https://leetcode-cn.com/problems/linked-list-cycle-ii/)

#### [24. 两两交换链表中的节点](https://leetcode-cn.com/problems/swap-nodes-in-pairs/)

----

#### [21. 合并两个有序链表](https://leetcode-cn.com/problems/merge-two-sorted-lists/)

#### [25.K 个一组翻转链表](https://leetcode-cn.com/problems/reverse-nodes-in-k-group/)

#### [86.分隔链表](https://leetcode-cn.com/problems/partition-list/)

#### [92.反转链表 II](https://leetcode-cn.com/problems/reverse-linked-list-ii/)

```c++
/**
 * Definition for singly-linked list.
 * struct ListNode {
 *     int val;
 *     ListNode *next;
 *     ListNode(int x) : val(x), next(NULL) {}
 * };
 */
class Solution {
public:
    ListNode* reverseBetween(ListNode* head, int m, int n) {
        int change_len = n - m + 1;
        ListNode *pre_head = NULL;
        ListNode *result = head;
        while (head && --m) {
            pre_head = head;
            head = head->next;
        }
        ListNode *modify_list_tail = head;
        ListNode *new_head = NULL;
        while (head && change_len--) {
            ListNode *next = head->next;
            head->next = new_head;
            new_head = head;
            head = next;
        }
        //连接为翻转部分
        modify_list_tail->next = head;
        if (pre_head) {
            pre_head->next = new_head;
        } else {
            result = new_head;
        }
        return result;
    }
};
```

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
     * @param ListNode $head
     * @param Integer $m
     * @param Integer $n
     * @return ListNode
     */
    function reverseBetween($head, $m, $n) {
        //计算翻转部分的长度
        $change_len = $n - $m + 1;
        $pre_head = null; //记录翻转区间的前驱节点
        $result = $head;
        while ($head && --$m) {
            $pre_head = $head;
            $head = $head->next;
        }
        //开始翻转
        $change_tail = $head;//记录翻转后的尾节点
        $new_head =null; //翻转后新的头节点
        while ($head && $change_len--) {
            $next = $head->next;
            $head->next = $new_head;
            $new_head = $head;
            $head = $next;
        }
        //连接未翻转部分
        $change_tail->next = $head;
        if ($pre_head) {
            $pre_head->next = $new_head;
        } else {
            //如果m为0，新的头结点
            $result = $new_head; 
        }
        return $result;
    }
}
```



#### [ 160.相交链表](https://leetcode-cn.com/problems/intersection-of-two-linked-lists/)

#### [35.复杂链表的复制](https://leetcode-cn.com/problems/fu-za-lian-biao-de-fu-zhi-lcof/)

#### [面试题52. 两个链表的第一个公共节点](https://leetcode-cn.com/problems/liang-ge-lian-biao-de-di-yi-ge-gong-gong-jie-dian-lcof/)

#### [面试题 02.04. 分割链表](https://leetcode-cn.com/problems/partition-list-lcci/)

### 栈&队列

#### [20. 有效的括号](https://leetcode-cn.com/problems/valid-parentheses/)

#### [232. 用栈实现队列](https://leetcode-cn.com/problems/implement-queue-using-stacks/)

```c++
//方法一
#include <stack>
class MyQueue{
  public:
  	MyQueue(){}
  	void push(int x){
      std::stack<int> temp_stack; 
      while (!_data.empty()) {
        temp_stack.push(_data.top());
        _data.pop();
      }
      temp_stack.push(x);
      while (!temp_stack.empty()) {
        _data.push(temp_stack.top());
        temp_stack.pop();
      }
    }
  	int pop(){
      int x = _data.top();
      _data.pop();
      return x;
    }
  	int peek(){
      return _data.top();
    }
  	bool empty(){
      return _data.empty();
    }
  private:
  	std::stack<int> _data;
};

//方法二
#include <stack>
class MyQueue{
  public:
  	MyQueue(){}
  	void push(int x) {
      _input.push(x);
    }
  	int pop() {
      adjust();
      int x = _output.top();
      _output.pop();
      return x;
    }
  	int peek() {
      adjust();
      return _output.top();
    }
  	bool empty() {
      return _input.empty() && _output.empty();
    }
  private:
  	void adjust() {
      if (!_output.empty()) {
        return;
      }
      while (!_input.empty()) {
        _output.push(_input.top());
        _input.pop();
      }
    }
  	std::stack<int> _input;
  	std::stack<int> _output;
};
```

#### [225. 用队列实现栈](https://leetcode-cn.com/problems/implement-stack-using-queues/)

```c++
#include <queue>
class MyStack {
  public:
  	MyStack(){}
  	void push(int x) {
      std::queue<int> temp_queue;
      temp_queue.push(x);
      while(!_data.empty()){
        temp_queue.push(_data.front());
        _data.pop();
      }
      while(!temp_queue.empty()){
        _data.push(temp_queue.front());
        temp_queue.pop();
      }
    }
    /*void push(int x) {
        _data.push(x);
        for (int i=1; i<_data.size(); i++) {
            _data.push(_data.front());
            _data.pop();
        }
    }*/
  	int pop() {
      int x = _data.front();
      _data.pop();
      return x;
    }
  	int top() {
      return _data.front();
    }
  	bool empty() {
      return _data.empty();
    }
  private:
  	std::queue<int> _data;
  
};
```

#### [155. 最小栈](https://leetcode-cn.com/problems/min-stack/)

```c++
class MinStack {
public:
    /** initialize your data structure here. */
    MinStack() {}
    void push(int x) {
			_data.push(x);//将数据压入数据栈
      if (_min.empty()) {
        //如果最小值栈空，直接将数据压入栈
        _min.push(x);
      } else {
        //比较当前数据与最小值栈顶数据大小，选择较小的压入最小值栈
        if (x > _min.top()){
          x = _min.top();
        }
        _min.push(x);
      }
    }
    void pop() {
      //数据栈和最小值栈同时出栈
			_data.pop();
      _min.pop();
    }
    int top() {
      //获取数据栈栈顶
			return _data.top();
    }
    int getMin() {
      //获取最小值栈栈顶
			return _min.top();
    }
  private:
  	std::stack<int> _data;//数据栈
  	std::stack<int> _min;//最小值栈
};
```

#### poj 1363 合法的出栈序列

```c++
#include <stack>
#include <queue>
//检测序列存储在队列中
bool check_is_valid_order(std::queue<int> &order) {
	std::stack<int> S;//S为模拟栈
  int n = queue.size();//n 为序列长度， 讲1-n按顺序入栈
  for (int i = 1; i <= n; i++) {
    S.push(i);//将元素i压入栈
    //如果栈不为空，栈顶和队列头部元素相等，即弹出元素
    while (!S.empty() && S.top() == order.front()) {
      S.pop();
      order.pop();
    }
  }
  if (!s.empty()) {
    return false;
  }
  return true;
} 
```



### 优先级队列（堆）

#### [703. 数据流中的第K大元素](https://leetcode-cn.com/problems/kth-largest-element-in-a-stream/)

#### [239. 滑动窗口最大值](https://leetcode-cn.com/problems/sliding-window-maximum/)

#### [215. 数组中的第K个最大元素](https://leetcode-cn.com/problems/kth-largest-element-in-an-array/)

```c++
#include <vector>
#include <queue>
class Solution {
public:
    int findKthLargest(vector<int>& nums, int k) {
      //最小堆
      std::priority_queue<int, std::vector<int>, std::greater<int>> Q;
      //遍历nums数组
      for (int i = 0; i < nums.size(); i++){
        if (Q.size() < k) {
          Q.push(nums[i]);
        } else if (nums[i] > Q.top()) {
          Q.pop();
          Q.push(nums[i]);
        }
      }
      return Q.top();//返回堆顶
    }
};
```

#### [295. 数据流的中位数](https://leetcode-cn.com/problems/find-median-from-data-stream/)

```c++
class MedianFinder {
public:
    /** initialize your data structure here. */
    MedianFinder() {
    } 
    void addNum(int num) {
      if (big_queue.empty()) {
        big_queue.push(num);
        return;
      }
			if (big_queue.size() == small_queue.size()) {
        if (num < big_queue.top()) {
          big_queue.push(num);
        } else {
          small_queue.push(num);
        }
      }  else if (big_queue.size() > small_queue.size()){
        if (num > big_queue.top()) {
          small_queue.push(num); 
        } else {
          small_queue.push(big_queue.top());
          big_queue.pop();
          big_queue.push(num);
        }
      } else if (big_queue.size() < small_queue.size()) {
       	if (num < small_queue.top()) {
          big_queue.push(num);
        } else {
          big_queue.push(small_queue.top());
          small_queue.pop();
          small_queue.push(num);
        }
      }
    }
    double findMedian() {
			if (big_queue.size() == small_queue.size()) {
        return (big_queue.top() + small_queue.top()) / 2;
      } else if (big_queue.size() < small_queue.size()) {
        return small_queue.top();
      } else {
        return big_queue.top();
      }
    }
private:
  std::priority_queue<double> big_queue;//最大堆
  std::priority_queue<double, std::vector<double>, std::greater<double>> small_queue;//最小堆
};
```



### 哈希表

#### [1. 两数之和](https://leetcode-cn.com/problems/two-sum/)

#### [15. 三数之和](https://leetcode-cn.com/problems/3sum/)





### 树&二叉树&二叉搜索树

#### [98. 验证二叉搜索树](https://leetcode-cn.com/problems/validate-binary-search-tree/)

#### [236. 二叉树的最近公共祖先](https://leetcode-cn.com/problems/lowest-common-ancestor-of-a-binary-tree/)

#### [235. 二叉搜索树的最近公共祖先](https://leetcode-cn.com/problems/lowest-common-ancestor-of-a-binary-search-tree/)

#### [543. 二叉树的直径](https://leetcode-cn.com/problems/diameter-of-binary-tree/)

```c++
/**
 * Definition for a binary tree node.
 * struct TreeNode {
 *     int val;
 *     TreeNode *left;
 *     TreeNode *right;
 *     TreeNode(int x) : val(x), left(NULL), right(NULL) {}
 * };
 */
class Solution {
public:
    int diameterOfBinaryTree(TreeNode* root) {
        dept(root);
        return ans;
    }
private:
    int ans = 0;
    int dept(TreeNode* root) {
        if (root == NULL) return 0;
        int l = dept(root->left);
        int r = dept(root->right);
        ans = max(ans, l + r);
      	return max(l, r) + 1;
    }
};
```

--------

#### [695. 岛屿的最大面积 03-15](https://leetcode-cn.com/problems/max-area-of-island/)

#### [365. 水壶问题](https://leetcode-cn.com/problems/water-and-jug-problem/)

```php
class Solution {

    /**
     * @param Integer $x
     * @param Integer $y
     * @param Integer $z
     * @return Boolean
     */
    function canMeasureWater($x, $y, $z) {
        return $z == 0 || $z <= $x + $y && $z % $this->gcd($x, $y) == 0;
    }
    function gcd($x, $y) {
        return $y == 0 ? $x : $this->gcd($y, $x % $y);
    }
}
```

```python
import collections
class Solution(object):
    def canMeasureWater1(self, x, y, z):
        """
        :type x: int
        :type y: int
        :type z: int
        :rtype: bool
        """
        if x + y < z:
            return False
        if x ==0 or y ==0:
            return z == 0 or x + y ==z
        return z % math.gcd(x, y) == 0
    def canMeasureWater2(self, x, y, z):
        """
        :type x: int
        :type y: int
        :type z: int
        :rtype: bool
        """
        if z < 0 or x + y < z: return False
        q = collections.deque([(0, 0)])
        visited = {(0, 0)}
        while len(q):
            a, b = q.popleft()
            if a == z or b == z or a + b == z:
                return True
            states = self._gen_states(a, b, x ,y)
            for state in states:
                if state not in visited:
                    q.append(state)
                    visited.add(state)
    def _gen_states(self, a, b, x, y):
        states = []
        # 清空
        states.append((0, b))
        states.append((a, 0))
        #填满水
        states.append((x, b))
        states.append((a, y))
        
        # a -> b 
        if a + b < y:
            states.append((0, a + b))
        else:
            states.append((a + b - y, y))
		# b -> a
        if a + b < x:
            states.append((a + b, 0))
		else:
            states.append((x, a + b -y))
        return states
     def canMeasureWater3(self, x, y, z):
        """
        :type x: int
        :type y: int
        :type z: int
        :rtype: bool
        """
        if z < 0 or x + y < z: return False
        q = collections.deque(0)
        visited = {0}
        while len(q):
            sum = q.pop()
            if sum == z:
                return True
            states = [
                sum + x ,
                sum + y ,
                sum - x ,
                sum - y ,
            ]
            for state in states:
                if state not in visited:
                    q.append(state)
                    visited.add(state)
        return false
```

