<?php
include_once "includes/init.php";

get_header();?>

<div class="container">
    <div class="row">
        <div class="col-sm-12">
            <?php if($_SESSION){?>
                <h1>Welcome, <?php echo $_SESSION['username'];?></h1>
            <?php }?>
        </div>
    </div>
</div>



<?php get_footer();
?>
