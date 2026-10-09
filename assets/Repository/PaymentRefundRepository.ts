import AbstractApiRepository from '@wexample/js-api-entity/Common/AbstractApiRepository';
import PaymentRefund from '../Entity/PaymentRefund.js';

export default class PaymentRefundRepository extends AbstractApiRepository<PaymentRefund> {
  static getEntityType() {
    return PaymentRefund;
  }
}
