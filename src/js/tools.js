export const normalizeAmount = (amount, decimalPlaces = 2) => {
  return amount / Math.pow(10, decimalPlaces);
};

export const getCardPrice = (what, cartData) => {
  if (!cartData || !cartData.length) return 0;
  for (let i = 0; i < cartData.length; i++) {
    if (cartData[i].key === what) {
      return normalizeAmount(cartData[i].value, wcSettings.currency.precision);
    }
  }

  return 0;
};

export const capitalizeFirstLetter = (string) => {
  return string.charAt(0).toUpperCase() + string.slice(1);
};

export const setExtraInfo = (pf, billing, cartData) => {
  pf
    .setCustomerInfo({
      firstName: billing?.billingData?.first_name,
      lastName: billing?.billingData?.last_name,
      email: billing?.billingData?.email,
      phoneNumber: billing?.billingData?.phone,
    })
    .setBillingAddress({
      firstName: billing?.billingData?.first_name,
      lastName: billing?.billingData?.last_name,
      email: billing?.billingData?.email,
      phoneNumber: billing?.billingData?.phone,
      streetAddress: billing?.billingData?.address_1,
      streetAddress2: billing?.billingData?.address_2,
      city: billing?.billingData?.city,
      state: billing?.billingData?.state,
      postalCode: billing?.billingData?.postcode,
      country: billing?.billingData?.country,
    })
    .setOrderInfo({
      taxAmount: getCardPrice('total_tax', billing?.cartTotalItems),
      shippingAmount: getCardPrice('total_shipping', billing?.cartTotalItems),
      discountAmount: getCardPrice('total_discount', billing?.cartTotalItems),
      orderItems: cartData?.cartItems?.map((item) => ({
        name: item.name,
        quantity: item.quantity || 1,
        sku: item.sku,
        unitPrice: normalizeAmount(Number(item.prices?.price), wcSettings.currency.precision),
        totalAmount: normalizeAmount(Number(item.totals?.line_total), wcSettings.currency.precision),
      })),
    });
};

export const hidePlaceOrderButton = () => {
  const elements = document.getElementsByClassName('wc-block-components-checkout-place-order-button');
  for (let i = 0; i < elements.length; i++) {
    elements[i].style.display = 'none';
  }
}

export const showPlaceOrderButton = () => {
  const elements = document.getElementsByClassName('wc-block-components-checkout-place-order-button');
  for (let i = 0; i < elements.length; i++) {
    elements[i].style.display = '';
  }
}

export const isSafari = () => {
  return /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
}
