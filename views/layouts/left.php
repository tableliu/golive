<?php
namespace yii\bootstrap;

use dmstr\widgets\Menu;
use mdm\admin\components\Helper;
use mdm\admin\components\MenuHelper;
use Yii;

?>
<aside class="main-sidebar">

    <section class="sidebar">
        <!-- Sidebar user panel -->
        <div class="user-panel">
            <div class="info">
                <p>
                    &nbsp;&nbsp;
                    <i class="fa fa-circle text-success"></i> Online
                </p>
           </div>
        </div>

        <!-- search form -->
        <form action="#" method="get" class="sidebar-form">
            <div class="input-group">
                <input type="text" name="q" class="form-control" placeholder="Search..."/>
                <span class="input-group-btn">
                <button type='submit' name='search' id='search-btn' class="btn btn-flat"><i class="fa fa-search"></i>
                </button>
              </span>
            </div>
        </form>

</aside>
