import payment from '../data/entity/payment.json';
import paymentRefund from '../data/entity/payment_refund.json';

type EntitySchema = { name: string };

export default function getGeneratedEntitySchemas(): Record<string, EntitySchema> {
  return {
    [payment.name]: payment,
    [paymentRefund.name]: paymentRefund,
  };
}
