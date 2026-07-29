<div
  class="vx-sendifico-checkout card mt-3"
  data-vx-sendifico-checkout
  data-ajax-url="{$vx_sendifico_checkout.ajax_url|escape:'htmlall':'UTF-8'}"
  data-token="{$vx_sendifico_checkout.ajax_token|escape:'htmlall':'UTF-8'}"
>
  <div class="card-block">
    <h4 class="h6 mb-2">Territorio de entrega Sendifico</h4>

    {if $vx_sendifico_checkout.message}
      <div
        class="alert {if $vx_sendifico_checkout.status == 'quoted'}alert-success{elseif $vx_sendifico_checkout.status == 'quote_failed' || $vx_sendifico_checkout.status == 'configuration_error'}alert-warning{else}alert-info{/if} mb-3"
        data-vx-sendifico-message
      >
        {$vx_sendifico_checkout.message|escape:'htmlall':'UTF-8'}
      </div>
    {/if}

    <div class="form-group mb-2">
      <label for="vx-sendifico-territory-select" class="form-control-label">Selecciona el territorio que corresponde a la entrega final.</label>
      <select
        id="vx-sendifico-territory-select"
        class="custom-select"
        data-vx-sendifico-select
        {if !$vx_sendifico_checkout.territories}disabled="disabled"{/if}
      >
        <option value="">Seleccionar territorio</option>
        {foreach from=$vx_sendifico_checkout.territories item=territory}
          <option
            value="{$territory.value|escape:'htmlall':'UTF-8'}"
            {if $territory.value == $vx_sendifico_checkout.selected_territory_base_id}selected="selected"{/if}
          >
            {$territory.label|escape:'htmlall':'UTF-8'}
          </option>
        {/foreach}
      </select>
    </div>

    <p class="small text-muted mb-0">
      {if $vx_sendifico_checkout.selected_territory_label}
        Territorio actual: {$vx_sendifico_checkout.selected_territory_label|escape:'htmlall':'UTF-8'}.
      {else}
        La lista de carriers se filtrará con base en este territorio y la cotización vigente.
      {/if}
    </p>
  </div>
</div>
