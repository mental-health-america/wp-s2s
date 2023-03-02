<?php if(get_field('display_modal_on_exit')): ?>

  <div class="modal fade" id="exitModal" tabindex="-1" role="dialog" aria-labelledby="exitModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header d-none"></div>
      <article class="modal-body">
        <?php the_field('modal_content'); ?>
      </article>
      <div class="modal-footer border-0">
        <button type="button" class="round-tl blue" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<?php endif; ?>