import { useEffect } from 'react';
import { normalizeAmount, setExtraInfo, showPlaceOrderButton } from './tools';

export const PayfurlPayToComponent = ( props ) => {
  console.log('[PayfurlPayToComponent]', props);
  const { eventRegistration, emitResponse, billing, cartData } = props;
  const { onPaymentSetup } = eventRegistration;
  let loaded = false;

  useEffect(() => {
    if (window._pf && props && !loaded) {
      showPlaceOrderButton();

      const amount = normalizeAmount(
        (billing?.cartTotal?.value || 0),
        wcSettings.currency.precision
      );
      const currency = wcSettings.currency.code;
      loaded = true;
      window._pf.addPayTo('payfurl-payto-component', amount);
      setExtraInfo(window._pf, billing, cartData);
    }
  }, []);

  useEffect( () => {
    const unsubscribe = onPaymentSetup( async () => {
      return new Promise( ( resolve, reject ) => {
        window._pf.onFailure((message) => {
          console.log("[PayfurlPayToComponent][Payfurl SDK onFailure]", message);
          reject({
            type: emitResponse.responseTypes.ERROR,
            message: message,
          });
        });
        window._pf.onSuccess((message) => {
          console.log("[PayfurlPayToComponent][Payfurl SDK onSuccess]", message);
          resolve({
            type: emitResponse.responseTypes.SUCCESS,
            meta: {
              paymentMethodData: {
                payfurl_token: message.token,
                payfurl_transaction_id: message.transactionId,
              },
            },
          });
        });
        window._pf.execute();
      });
    } );
    return () => {
      unsubscribe();
    };
  }, [
    emitResponse.responseTypes.ERROR,
    emitResponse.responseTypes.SUCCESS,
    onPaymentSetup,
  ] );

  return (
    <div id="payfurl-payto-component"></div>
  );
};
