
<div class="dropdown text-right pr-0 pr-md-4 mb-4">
    <label class="inline text-dark-blue small bold">Sort by: &nbsp; </label>
    <button class="button gray round-br dropdown-toggle normal-case" type="button" id="orderSelection" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-order="DESC" value="featured">
        <?php
            if(get_query_var('filter_order')){
                echo get_query_var('filter_order');
            } else {
                echo 'Default';
            }
        ?>
    </button>
    <div class="dropdown-menu" aria-labelledby="orderSelection">
        <button class="dropdown-item normal-case filter-order" type="button" data-order="ASC" value="featured">Featured</button>
        <button class="dropdown-item normal-case filter-order" type="button" data-order="ASC" value="title">Title A-Z</button>
        <button class="dropdown-item normal-case filter-order" type="button" data-order="DESC" value="title">Title Z-A</button>
        <button class="dropdown-item normal-case filter-order" type="button" data-order="DESC" value="date">Newest First</button>
        <button class="dropdown-item normal-case filter-order" type="button" data-order="ASC" value="date">Oldest First</button>
    </div>
</div>