<div>
    <div class="position-relative">
        <div style="height: 0; overflow: hidden; position: absolute;" aria-hidden="true">
            <input type="text" name="fake_username_anti_autofill" tabindex="-1" autocomplete="off">
            <input type="password" name="fake_password_anti_autofill" tabindex="-1" autocomplete="off">
        </div>
        <input type="text" 
               class="form-control ps-5" 
               wire:model.live.debounce.500ms="searchTerm" 
               placeholder="Buscar..." 
               maxlength="55"
               name="buscador_sistema_unico" 
               autocomplete="off" 
               spellcheck="false">
        <span class="position-absolute product-show translate-middle-y" style="top: 55%;"><i
                class="bx bx-search-alt"></i></span>
    </div>
</div>