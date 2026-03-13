<footer class="site-credit-footer">
    <div class="site-credit-org">DepEd &mdash; Schools Division Office of San Pedro City</div>
    <div class="site-credit-lines">
        <span>Project Directive: Engr. Lyka Jane A. Leosala - IT Officer 1</span> 
        <span>System Development: ICT Unit</span> 
        <span>Developed by: ICT Interns (AJ.E, R.P, A.L, C.B)</span> 
        <span>Maintained by: ICT Clerks</span>
    </div>
    <img src="/assets/img-ref/sdoClick.png" alt="SDO Click" class="site-credit-logo">
</footer>
</main>
</div><!-- /.app-layout -->
<?php if (!empty($_bottomNavHtml)) echo $_bottomNavHtml; ?>
<script src="/assets/js/app.js"></script>
<?php if (isset($pageScripts)): ?>
    <?php foreach ($pageScripts as $script): ?>
        <script src="<?= $script ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
<?php if (isset($inlineScript)): ?>
    <script><?= $inlineScript ?></script>
<?php endif; ?>
</body>
</html>
