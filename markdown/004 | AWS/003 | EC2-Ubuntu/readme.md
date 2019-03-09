# EC2 上的 Ubuntu

* 以下會教學新的 Ubuntu 常用的幾項基本設定，主要有 `Git`、`Zsh`、`SSH`、`Let's Encrypt` 設定。
* 經過設定 `Elastic IPs` 後，我們就可以藉由其 IP 與下載的 .pem 檔案做第一次的 ssh 連線遠端登入 Server

## 假設
* 以下操作先做假設
	* Elastic IPs：`123.456.789`
	* 下載的 .pem 檔案名稱：`test.pem`
	* `test.pem` 位置：`/User/oa/test.pem`
	* 網址(domain)：`your.url.tw`
	* E-Mail：`your.email@gmail.com`
	* 以下編輯器主要是使用 `nano`，請自行斟酌是否使用 `vi` 或 `vim`

> 記得去你的 DNS(Domain Name Server) 設定，新增 `A` 紀錄 `your.url.tw`，指向 IP `123.456.789`

## 首次登入

* 先設定 `test.pem` 檔案的權限，指令：`chmod 400 /User/oa/test.pem`
* 以 `ssh` 方式，藉由 `test.pem` 登入 Server，輸入指令：`ssh ubuntu@123.456.789 -i /User/oa/test.pem`，第一次會問你要不要加入此 IP 的紀錄，你就輸入 `yes` 後按下 `enter` 即可。

## 安裝 Git

* 安裝 Git，指令：`sudo apt install git`
* 檢查版本，確認是否安裝成功，指令：`git --version`

## 安裝 Oh My Zsh

* 修改 `chsh` 權限，編輯指令：`sudo nano /etc/pam.d/chsh`

* 將 `auth required pam_shells.so` 改為  `auth sufficient pam_shells.so`

	> 改為 `auth sufficient pam_shells.so` 主要是因為後面更改 Shell 都會詢問密碼，但是你是藉由 `test.pem` 方式登入，所以沒有密碼，因此才改為 `sufficient`

* 安裝 `zsh`，指令：`sudo apt install zsh`，中間會問你 `Do you want to continue? [Y/n]`，按下 `Y` 後 `enter` 即可。

* 使用 `oh-my-zsh` 主題，指令：`curl -L https://raw.github.com/robbyrussell/oh-my-zsh/master/tools/install.sh | sh`

* 重新 ssh 連線，輸入指令：`exit`，然後再重新連線，指令：`ssh ubuntu@123.456.789 -i /User/oa/test.pem`

## 設定時間

* 初始的 Ubuntu 會有時間誤差，所以需要校正，並且設定 Local 的時間
* 設定本地區域，輸入指令：`sudo timedatectl set-timezone Asia/Taipei`
* 校正時間，檢查時間與本地端(你的電腦)是否正確，輸入指令：`timedatectl`

## 設定語系
* 設定中文語系，沒設定會有亂碼，指令：`sudo locale-gen zh_TW.UTF-8`

## 產生 SSH Key
* 輸入指令：`ssh-keygen -t rsa -C "your.email@gmail.com"`
* 連續按下幾個 `enter` 後即可完成。

## 藉由 Authorized Keys 登入
* 開啟本機端的終端機，複製 Public key，輸入指令：`pbcopy < ~/.ssh/id_rsa.pub`
* 上 Server，輸入指令：指令：`ssh ubuntu@123.456.789 -i /User/oa/test.pem`
* 加入你的 key 到 `authorized_keys`，輸入指令：`nano ~/.ssh/authorized_keys`
* 在 `authorized_keys` 內的檔案最後面加上你剛剛複製的本機端 Public key
* 如此一來，你就不用每次都要藉由 `test.pem` 的方式登入，但也記得請好好的保存 `test.pem`










## Let's Encrypt(ssl)

* 安裝 curl，指令 `sudo apt install curl`
* 進入 www 目錄，指令 `cd ~/www`
* 從 Git 下載最新 dehydrated，指令 `git clone https://github.com/lukas2511/dehydrated.git`
* 進入專案，指令 `cd dehydrated`
* 新增目錄，指令 `sduo mkdir /etc/dehydrated/`
* 修改權限，指令 `sudo chmod 777 /etc/dehydrated/`
* 將檔案移進去，指令 `cp dehydrated /etc/dehydrated/`
* 修改 dehydrated 權限 `chmod a+x /etc/dehydrated/dehydrated`
* 建立證驗時所需目錄，指令 `mkdir -p /var/www/dehydrated/`

* 第一次執行同意 Let's Encrypt 的條款，指令 `/etc/dehydrated/dehydrated --register --accept-terms`


## 新增 SSL by Let's Encrypt
* 以下用 ``
* Apache
  * 在該專案下的 `http` vhost 內，加入指令 `Alias /.well-known/acme-challenge/ /var/www/dehydrated/`

* nginx
  * 在該專案下的 `http` vhost 中的 server 內加入以下指令：

```
location /.well-known/acme-challenge/ {
    alias /var/www/dehydrated/;
}
```

* 重新載入 Apache 設定，指令：`sudo systemctl reload apache2`

* 取得憑證 `/etc/dehydrated/dehydrated -c -d your.url.tw`


## 啟用 Apache SSL 功能

* 指令：`sudo a2enmod ssl`

* 複製一份 ssl vhost 檔案指令 `sudo cp /etc/apache2/sites-available/default-ssl.conf /etc/apache2/sites-available/my-ssl.conf`

* 啟用 `my-ssl.conf`，指令：`sudo a2ensite my-ssl.conf`

* 重新載入 Apache 設定，指令：`sudo systemctl reload apache2`

* 編輯 `my-ssl.conf`，指令：`sudo nano /etc/apache2/sites-available/my-ssl.conf`，可以用以下當範例：

```
<IfModule mod_ssl.c>

  <VirtualHost _default_:443>
    # 你的 Domain
    ServerName  your.url.tw
    ServerAlias your.url.tw

    # 你的 E-Mail
    ServerAdmin your.email@gmail.com

    # 你的專案目錄
    DocumentRoot /var/www

    # Log 的儲存位置
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
	
	 ## SSL 設定
    SSLEngine on
    SSLCertificateFile /etc/dehydrated/certs/your.url.tw/cert.pem
    SSLCertificateKeyFile /etc/dehydrated/certs/your.url.tw/privkey.pem
    SSLCertificateChainFile /etc/dehydrated/certs/your.url.tw/chain.pem

    # 記得也要是你的專案目錄
    <Directory /var/www>
      Options FollowSymLinks MultiViews
      AllowOverride All
      Order allow,deny
      Allow from all
    </Directory>
  </VirtualHost>

</IfModule>
```

* 重啟 Apache 即可，指令：`sudo service apache2 restart`

* 若出現以下錯誤，代表你還沒申請 ssl 的憑證檔案，所以出錯。

```
Job for apache2.service failed because the control process exited with error code.
See "systemctl status apache2.service" and "journalctl -xe" for details.
```

* 所以，先把 `my-ssl.conf` 停用再重開 Apache，指令：`sudo a2dissite my-ssl.conf`
* 重新載入 Apache 設定，指令：`sudo systemctl reload apache2`
* 重啟 Apache 即可，指令：`sudo service apache2 restart`
* 然後 執行上述的 `新增 SSL by Let's Encrypt`


## 以上參考：

* [http://comdan66.github.io/configs/book/mds/ec2-ubuntu/apache.html](http://comdan66.github.io/configs/book/mds/ec2-ubuntu/apache.html)

* [https://note.artchiu.org/2016/06/17/lets-encrypt-%E4%BD%BF%E7%94%A8%E8%AA%AA%E6%98%8E-%E9%9D%9E%E5%AE%98%E6%96%B9/](https://note.artchiu.org/2016/06/17/lets-encrypt-%E4%BD%BF%E7%94%A8%E8%AA%AA%E6%98%8E-%E9%9D%9E%E5%AE%98%E6%96%B9/)

`#AWS` `#EC2` `#Git` `#Zsh` `#SSH` `#Let's Encrypt` `#https`