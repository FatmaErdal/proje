<?php
include "tmplKontrol.php";
?>

<section>
    <div class="col-md-4 col-md-offset-4">
        <div class="login-panel panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    Kullanıcı mailinizi giriniz.
                </h3>
            </div>
            <div class="panel-body">
                <form action="?sayfa=sifirla&durum=mail" method="POST">
                    <div class="form-group">
                        <input class="form-control" type="email" autofocus="" name="fMail" placeholder="E-mail">
                    </div>
                    <input class="btn btn-lg btn-block btn-one" type="submit" value="Yolla" name="sifre"/>
                </form>
            </div>
            <?php echo $mesaj; ?>
        </div>
    </div>
</section>

