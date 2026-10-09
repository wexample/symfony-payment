import AbstractApiEntity from '@wexample/js-api-entity/Common/AbstractApiEntity';
import schema from '../data/entity/payment_refund.json';

export default class PaymentRefund extends AbstractApiEntity {
  static readonly entityName = 'paymentRefund';

  static retrieveEntitySchema() {
    return schema;
  }
}
