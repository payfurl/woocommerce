import { useEffect } from 'react';
import { hidePlaceOrderButton, normalizeAmount, setExtraInfo } from './tools';

export const PayfurlCheckoutComponent = (props) => {
  console.log('[PayfurlCheckoutComponent]', props);
  const { eventRegistration, emitResponse, billing, cartData } = props;
  const { provider } = props;
  const { onPaymentSetup } = eventRegistration;
  let loaded = false;
  let transactionId = '';


  useEffect(() => {
    if (window._pf && props && !loaded) {
      hidePlaceOrderButton();

      const amount = normalizeAmount(
        (billing?.cartTotal?.value || 0),
        wcSettings.currency.precision,
      );
      const currency = wcSettings.currency.code;
      loaded = true;
      setExtraInfo(window._pf, billing, cartData);
      window._pf
        .addCheckout('payfurl-'+provider.providerType+'-component', amount, currency, provider.providerType, provider.providerId, {}, provider.options)
        .onFailure((message) => {
          console.log('[PayfurlCheckoutComponent][Payfurl SDK onFailure]', message);
        })
        .onSuccess((message) => {
          console.log('[PayfurlCheckoutComponent][Payfurl SDK onSuccess]', message);
          transactionId = message.transactionId;
          const elements = document.getElementsByClassName('wc-block-components-checkout-place-order-button');
          elements[0].click();
        });
    }
  }, []);

  useEffect(() => {
    const unsubscribe = onPaymentSetup(async () => {
      return new Promise((resolve, reject) => {
        console.log('[PayfurlCheckoutComponent], onPaymentSetup');
        if (!transactionId) {
          return reject({
            type: emitResponse.responseTypes.ERROR,
            message: 'PayPal transaction was not completed',
          });
        }

        resolve({
          type: emitResponse.responseTypes.SUCCESS,
          meta: {
            paymentMethodData: {
              payfurl_transaction_id: transactionId,
            },
          },
        });
      });
    });
    return () => {
      unsubscribe();
    };
  }, [
    emitResponse.responseTypes.ERROR,
    emitResponse.responseTypes.SUCCESS,
    onPaymentSetup,
  ]);

  return (
    <div id={ `payfurl-${provider.providerType}-component` }></div>
  );
};
