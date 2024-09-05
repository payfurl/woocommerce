import { useEffect } from 'react';
import { hidePlaceOrderButton, normalizeAmount, setExtraInfo } from './tools';

export const PayfurlPaypalComponent = (props) => {
  console.log('[PayfurlPaypalComponent]', props);
  const { eventRegistration, emitResponse, billing, cartData } = props;
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
      window._pf
        .addPaypal('payfurl-paypal-component', amount, currency)
        .onFailure((message) => {
          console.log('[PayfurlPaypalComponent][Payfurl SDK onFailure]', message);
        })
        .onSuccess((message) => {
          console.log('[PayfurlPaypalComponent][Payfurl SDK onSuccess]', message);
          transactionId = message.transactionId;
          const elements = document.getElementsByClassName('wc-block-components-checkout-place-order-button');
          elements[0].click();
        });
      setExtraInfo(window._pf, billing, cartData);
    }
  }, []);

  useEffect(() => {
    const unsubscribe = onPaymentSetup(async () => {
      return new Promise((resolve, reject) => {
        console.log('[PayfurlPaypalComponent], onPaymentSetup');
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
    <div id="payfurl-paypal-component"></div>
  );
};
