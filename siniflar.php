﻿<?php

class Bulut
{
    public $DB;

    function __construct()
    {
        $host = "localhost";
        $dbname = "bulut";
        $user = "root";
        $pass = "";
        //$dsn = "mysql:host=$host;dbname=$dbname";
	$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8";

        try {
            $this->DB = new PDO($dsn, $user, $pass);
            //$this->DB->exec("SET CHARACTER SET utf8");
        } catch (PDOException $e) {
            echo "[HATA]: Veritabanı -".$e->getMessage();
        }
    }

    /**
     * Kullanıcı ve Şirket arasında normalizasyon işlemi.
     *
     * @not: parametreler vs $_COOKIE'den alınacak kayıt işlemi
     * sırasında.
     *
     * @param $id_kullanici     (int) Kullanıcı id'si.
     * @param $id_sirket        (int) Şirket id'si.
     * @return bool
     */
    public static
    function normalizasyonSirket($id_kullanici, $id_sirket)
    {

        // static bir bağlantı kuruyoruz sınıf ile böylece
        // static fonksiyonlar construct veritabanına ulaşabiliyor.
        $obj = new static();
        $db = $obj->DB;

        // Sorgunun hazırlanması.
        $sorgu = $db->prepare("INSERT INTO kullanicilar_sirket (id, id_kullanici, id_sirket) VALUES (NULL, ?, ?)");
        $islem = $sorgu->execute(array($id_kullanici, $id_sirket));

        if ($islem) {
            return true;
        }
        else {
            return false;
        }
    }


    /**
     * Kullanıcı ve Roller arasında normalizasyon işlemi.
     *
     * @not: parametreler vs $_COOKIE'den alınacak kayıt işlemi
     * sırasında.
     *
     * @param $id_kullanici (int) Cookie'den alınan kullanıcı id'si.
     * @param $id_rol       (int) Cookie'den alınan rol id'si
     * @return bool
     */
    public static
    function normalizasyonRoller($id_kullanici, $id_rol)
    {


        // static bir bağlantı kuruyoruz sınıf ile böylece
        // static fonksiyonlar construct veritabanına ulaşabiliyor.
        $obj = new static();
        $db = $obj->DB;

        // Sorgunun hazırlanması.
        $sorgu = $db->prepare("INSERT INTO kullanicilar_roller(id, id_kullanici, id_rol) VALUES (NULL, ?, ?)");
        // !!!: Bir sebepten bindParam çalışmıyor.
        $islem = $sorgu->execute(array($id_kullanici, $id_rol));

        if ($islem) {
            return true;
        }
        else {
            return false;
        }
    }


    /**
     *
     * Verilen kullanıcı id'si ile kullanıcı rolünün getirilmesi.
     * Eğer kullaniciRol(1, $DB, true) şeklinde kullanılırsa
     * rol'ün id'si yerine açık ismini getirir.
     *
     *
     * ÖNEMLİ:
     * ~~~~~~~
     * Bu fonksiyonun return ile kontroller de if(true) vs
     * yerine if($rol>=0) gibi bir kullanım gerekli. SüperAdmin rolü
     * "0" dönüyor. if($rol)'de bu durum FALSE a sebep olacaktır.
     *
     * @param $id_kullanici     Kullanıcı id'si
     * @param $DB               Veritabanı bağlantısı.
     * @param $aciklama bool    Rol id'si sayi yerine isim olarak mı gelsin?
     * @return array|string
     */
    public static
    function kullaniciRolu($id_kullanici, $aciklama=false)
    {

        // static bir bağlantı kuruyoruz sınıf ile böylece
        // static fonksiyonlar construct veritabanına ulaşabiliyor.
        $obj = new static();
        $db = $obj->DB;

        // Kullanıcı rolunun veritabanından çekilmesi.
        $sorgu = $db->prepare("SELECT id_rol FROM kullanicilar_roller WHERE id_kullanici = :id_kullanici");
        $sorgu->bindParam(":id_kullanici", $id_kullanici);
        $sorgu->execute();

        $sonuc = $sorgu->fetchAll(PDO::FETCH_ASSOC);

        if ($sonuc) {
            // İleride bir kullanıcıya birden fazla rol verme ihtimali
            // doğmasına karşın.

            if (count($sonuc) > 1) {
                // Eğer birden fazla rol döner ise array dön.
                $roller = array();
                foreach($sonuc as $son) {
                    $roller[] = $aciklama ? Bulut::rolIsim($son["id_rol"]): $son["id_rol"];
                }

                return $roller;
            }
            else {
                $rol = $aciklama ? Bulut::rolIsim($sonuc[0]["id_rol"]): $sonuc[0]["id_rol"];
                return $rol;
            }

        }
        else {
            // !ÖNEMLİ: false değil -1 dönüyor.
            return "-1";
        }

    }

    /**
     * Verilen rol id'si ile rol'ün tam ismini getirir.
     *
     * NOT: kullaniciRol() ile kullanmak içen özellikle.
     * @param $id_rol       Rol id'si
     * @return bool|string
     */
    public static
    function rolIsim($id_rol)
    {

        // static bir bağlantı kuruyoruz sınıf ile böylece
        // static fonksiyonlar construct veritabanına ulaşabiliyor.
        $obj = new static();
        $db = $obj->DB;

        // sorgu işlemi.
        $sorgu = $db->prepare("SELECT rol FROM roller WHERE id= :id_rol");
        $sorgu->bindParam(":id_rol", $id_rol);
        $sorgu->execute();

        $sonuc = $sorgu->fetch(PDO::FETCH_ASSOC);

        if($sonuc) {
            return $sonuc["rol"];
        }
        else {
            return false;
        }
    }


    public static
    function oturumAc($mail, $sifre, $hatirla=false) //default false olsun. gelince değiştiririz.
    {
        // Statik sınıf işlemleri.
        $obj=new static();
        $db=$obj->DB;
        
	    $mail = trim($mail);
        $sifre = md5(trim($sifre));

        $sorgu = $db->prepare("SELECT * FROM kullanicilar WHERE mail = :mailAdres and sifre = :sifre LIMIT 1");
        $sorgu->bindValue(':mailAdres', $mail);
        $sorgu->bindValue(':sifre',  $sifre);
        $sorgu->execute();
        $kontrol = $sorgu->fetch(PDO::FETCH_ASSOC);

        if (!empty($kontrol)){
            $row_id = $kontrol['id'];
            $mail=$kontrol["mail"];
            $adi=$kontrol["adi"]." ".$kontrol["soyadi"];

            // Session oluşturumu.
            $_SESSION['kulId'] = $row_id;
            $_SESSION['kulAdi'] = $adi;
            $_SESSION['kulMail'] = $mail;
            $_SESSION['kulRol'] = Bulut::kullaniciRolu($row_id);
            if($hatirla) {
                setcookie("hatirla", true, time() + 60 * 60 * 24);
                setcookie("kulId", $row_id,time()+60*60*24);
                setcookie("kulAdi", $adi,time()+60*60*24);
                setcookie("kulMail", $mail,time()+60*60*24);
                setcookie("kulRol", Bulut::kullaniciRolu($row_id),time()+60*60*24);
            }
//            var_dump($_SESSION);
            return true;
//            echo "<script> window.location.href='default.php';</script>";

        }else{
            return false;
        }

    }
    
    public static
    function beniHatirlaKontrol()
    {
    	// return isset($_COOKIE["hatirla"]) && $_COOKIE[hatirla] ? true : false;
    	return isset($_COOKIE["hatirla"]) && $_COOKIE["hatirla"];
    	///asdasdasdasdasd
    	// sadece return demek yeterli. Çünkü buradan direkt true veya false gelecek.
    }
    public static
    function sifreSifirlamaKeyOlustur()
        // function sifre_sifirlama_key_olustur ()
    {

        $key = "";

        $katar = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $katar = str_split($katar);
        $katar_uzunluk = count($katar) - 1;

        for ($sinir = 0; $sinir < 50; $sinir ++) {
            $rand = rand(0, $katar_uzunluk);
            $key .= $katar[$rand];
        }
        $url = SITEURL.'/?sayfa=sifirla&key='.$key;
        $prelink = SITEURLSPAN.'/?sayfa=sifirla&key='.$key;
        $link = '<a href="'.SITEURL.'/?sayfa=sifirla&key=' . $key . '">' . $url . '</a>';


        return array(
            $key,
            $link,
            $prelink
        );
        // Sonuç array döner.
        // [0]. eleman veritabanına girilmek üzere sadece key i verir.
        // [1]. eleman da mailde gönderilmek üzere link haline getirilmiş değeri verir.
        // [2]. eleman da mailde gönderilmek üzere string halde link i verir.
    }

    public static
    function sirketEkle($adi,$adres,$tel,$logo,$sektor,$premium,$ref_kod,$tarih){
        // static bir bağlantı kuruyoruz sınıf ile böylece
        // static fonksiyonlar construct veritabanına ulaşabiliyor.
        $obj = new static();
        $db = $obj->DB;

        // Sorgunun hazırlanması.
        $sorgu = $db->prepare("INSERT INTO sirket  VALUES (NULL, ?,?,?,?,?,?,?,?)");
        $islem = $sorgu->execute(array($sektor,$adi,$adres,$tel,$logo,$premium,$ref_kod,$tarih));

        if ($islem) {
            return true;
        }
        else {
            return false;
        }
    }


}

?>
