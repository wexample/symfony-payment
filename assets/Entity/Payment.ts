import AbstractApiEntity from '@wexample/js-api-entity/Common/AbstractApiEntity';
import schema from '../data/entity/payment.json';

export default class Payment extends AbstractApiEntity {
  static readonly entityName = 'payment';

  static retrieveEntitySchema() {
    return schema;
  }
}
