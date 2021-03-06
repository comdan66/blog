# OAHUD

因為看到很多 GitHub 上的專案都寫 HUD，這幾天再研究一種東西，一開始我還不知道怎去下關鍵字 Google，不過找著找著 漸漸有方向了！所以就找了一些範例，以及 Google 找資料亂寫亂測，然後有了一點點心得，就來分享一下，如果有觀念不正確請各位指教！

簡略講解一下，首先我看了 [MBProgressHUD](https://github.com/jdg/MBProgressHUD) 的做法，其中的 `showHUDAddedTo:(UIView *)view` 讓我也模仿著做，於是我做了一個類似的 show，然後加在該 view 之下，一開始還做得很開心，不過當我用 TabBarController、NavigationController 時發現，只有中間的 view 區段被覆蓋，當中的 NavBar 跟 TabBar 都沒有被覆蓋到..於是我只好換個方向。

![OAHUD 動畫 Demo](img/001.gif)

接著我 Google 到了這篇文章 [http://nobodyyu.github.io/2015/05/04/make-HUD-by-your-self/](http://nobodyyu.github.io/2015/05/04/make-HUD-by-your-self/)，我於似乎看到了關鍵字 window，所以接著開始研究 UIWindow，接著開始使用 UIWindow 然後加入 ViewController 實驗！

當中當然一直失敗啊，直到我看到了 [DaiInboxHUD](https://github.com/DaidoujiChen/DaiInboxHUD) 這個資源，恩..很多看不懂ＸＤ，不過今天下午還是抽空把它嗑了！因為這包 Code 我看到了很多用法，其中像是 objc/runtime.h、objc_setAssociatedObject、objc_getAssociatedObject 雖然我還沒有很熟，所以有使用錯誤的話請跟我說..裡面像是彈出的動畫，也參考了它！

![預設的 OAHUD 樣式](img/002.jpg)

原本我打算找尋是否有像 css 中的 transition 搭配 cubic-bezier 的方法，但是一直沒找到..所以跳出的效果，所以我就暫時的學別人的方法使用大量的 animateWithDuration 以及 CGAffineTransformScale，不過看到這樣的寫法，讓我不禁回想起 jQuery 的 animate 搭配 callback 一起使用ＸＤ

![多樣的樣式設定](img/003.jpg)

不過使用起來沒有想像中的頓，於是先這樣使用啦！中間旋轉的部分是使用兩張 view，第一層的 view 加上 CAGradientLayer 的彩色圖層以及 CABasicAnimation 來達到選轉效果，然後第二層的 view 使用與底層一樣的顏色，並且縮小一點點將第一層蓋住，這樣就可以做出類似旋轉的彩色圈圈！

### 相關參考
* [Live Demo](https://github.com/comdan66/OAHUD/blob/master/OAHUD.gif)
* [GitHub 原始碼](https://github.com/comdan66/OAHUD)
* [PTT MacDev](https://www.ptt.cc/bbs/MacDev/M.1436456012.A.ACC.html)
* [MBProgressHUD](https://github.com/jdg/MBProgressHUD)
* [DaiInboxHUD](https://github.com/DaidoujiChen/DaiInboxHUD)
* [make HUD by your self](http://nobodyyu.github.io/2015/05/04/make-HUD-by-your-self/)

`#iOS` `#Object-C` `#HUD` `#App`