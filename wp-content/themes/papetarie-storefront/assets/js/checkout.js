(function ($) {
  const CUSTOMER_TYPE_FIELD = '#billing_customer_type_field';
  const COMPANY_ONLY_SELECTOR = '.pap-company-only';

  const toggleCompanyFields = () => {
    const selected = $('input[name="billing_customer_type"]:checked').val() || 'person';
    const isCompany = selected === 'company';

    $(COMPANY_ONLY_SELECTOR).each(function () {
      const $field = $(this);
      const $input = $field.find('input, select, textarea').first();

      if (isCompany) {
        $field.show().attr('aria-hidden', 'false');
        $input.prop('disabled', false);
      } else {
        $field.hide().attr('aria-hidden', 'true');
        $input.prop('disabled', true);
      }
    });
  };

  const ensureStyles = () => {
    if (!$(CUSTOMER_TYPE_FIELD).length) {
      return;
    }
    toggleCompanyFields();
  };

  $(document.body).on('change', 'input[name="billing_customer_type"]', toggleCompanyFields);
  $(document.body).on('updated_checkout', ensureStyles);
  $(ensureStyles);
})(jQuery);
