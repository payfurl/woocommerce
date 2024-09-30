import { getSetting } from '@woocommerce/settings';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { PayfurlCardComponent } from './payfurl-card';
import { PayfurlPaypalComponent } from './payfurl-paypal';
import { PayfurlCheckoutComponent } from './payfurl-checkout';
import { capitalizeFirstLetter, isSafari } from './tools';
import { PayfurlPayToComponent } from './payfurl-payto';
import { PayfurlGooglePayComponent } from './payfurl-googlepay';
import { PayfurlApplePayComponent } from './payfurl-applepay';

const settings = getSetting('payfurl_data');
console.log('settings', settings)

const SavedTokenHandler = (props) => {
  console.log('SavedTokenHandler', props);
  return <div>SavedTokenHandler</div>;
};

if (settings?.providersInfo?.hasCardProviders) {
  registerPaymentMethod({
    name: 'payfurl_card',
    paymentMethodId: 'payfurl_card',
    content: <PayfurlCardComponent />,
    edit: <PayfurlCardComponent />,
    canMakePayment: (params) => new Promise((resolve) => {
      resolve(true);
    }),
    savedTokenComponent: <SavedTokenHandler />,
    label: <strong>{settings.title}</strong>,
    ariaLabel: 'PayFURL Payments',
    supports: {
      features: ['products'],
      showSavedCards: true,
      showSaveOption: true,
    },
  });
}

if (settings?.providersInfo?.hasPaypalProviders) {
  registerPaymentMethod({
    name: 'payfurl_paypal',
    paymentMethodId: 'payfurl_paypal',
    content: <PayfurlPaypalComponent />,
    edit: <PayfurlPaypalComponent />,
    canMakePayment: (params) => new Promise((resolve) => {
      resolve(true);
    }),
    label: <strong>PayPal</strong>,
    ariaLabel: 'PayFURL Payments',
    supports: {
      features: ['products'],
      showSavedCards: false,
      showSaveOption: false,
    },
  });
}

if (settings?.providersInfo?.hasPayToProviders) {
  registerPaymentMethod({
    name: 'payfurl_payto',
    paymentMethodId: 'payfurl_payto',
    content: <PayfurlPayToComponent />,
    edit: <PayfurlPayToComponent />,
    canMakePayment: (params) => new Promise((resolve) => {
      resolve(true);
    }),
    label: <strong>PayTo</strong>,
    ariaLabel: 'PayFURL Payments',
    supports: {
      features: ['products'],
      showSavedCards: false,
      showSaveOption: false,
    },
  });
}

if (settings?.enable_googlepay && settings?.providersInfo?.hasGooglePayProviders) {
  registerPaymentMethod({
    name: 'payfurl_googlepay',
    paymentMethodId: 'payfurl_googlepay',
    content: <PayfurlGooglePayComponent />,
    edit: <PayfurlGooglePayComponent />,
    canMakePayment: (params) => new Promise((resolve) => {
      resolve(true);
    }),
    label: <strong>Google Pay</strong>,
    ariaLabel: 'PayFURL Payments',
    supports: {
      features: ['products'],
      showSavedCards: false,
      showSaveOption: false,
    },
  });
}

if (isSafari() && settings?.enable_applepay && settings?.providersInfo?.hasApplePayProviders) {
  registerPaymentMethod({
    name: 'payfurl_applepay',
    paymentMethodId: 'payfurl_applepay',
    content: <PayfurlApplePayComponent />,
    edit: <PayfurlApplePayComponent />,
    canMakePayment: (params) => new Promise((resolve) => {
      resolve(true);
    }),
    label: <strong>Apple Pay</strong>,
    ariaLabel: 'PayFURL Payments',
    supports: {
      features: ['products'],
      showSavedCards: false,
      showSaveOption: false,
    },
  });
}

if (settings?.providersInfo?.hasBnplProviders) {
  settings?.providersInfo?.bnplProviders.forEach((provider) => {
    registerPaymentMethod({
      name: 'payfurl_'+provider.providerType,
      paymentMethodId: 'payfurl_'+provider.providerType,
      content: <PayfurlCheckoutComponent provider={ provider } />,
      edit: <PayfurlCheckoutComponent provider={ provider } />,
      canMakePayment: (params) => new Promise((resolve) => {
        resolve(true);
      }),
      label: <strong>{ getTitleByProviderType(provider.providerType) }</strong>,
      ariaLabel: 'PayFURL Payments',
      supports: {
        features: ['products'],
        showSavedCards: false,
        showSaveOption: false,
      },
    });
  });
}

function getTitleByProviderType(providerType) {
  switch (providerType) {
    case 'azupay': return 'PayID';
    case 'pay_by_account': return 'Pay by Account';
    case 'upi': return 'UPI';
    default: return capitalizeFirstLetter(providerType);
  }
}
