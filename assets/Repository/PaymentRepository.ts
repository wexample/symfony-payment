import AbstractApiRepository from '@wexample/js-api-entity/Common/AbstractApiRepository';
import Payment from '../Entity/Payment.js';

export default class PaymentRepository extends AbstractApiRepository<Payment> {
  static getEntityType() {
    return Payment;
  }
}
