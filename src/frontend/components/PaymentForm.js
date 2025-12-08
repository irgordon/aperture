import { useState } from 'react';
import { CardElement, useStripe, useElements } from '@stripe/react-stripe-js';

const PaymentForm = ({ clientSecret }) => {
    const stripe = useStripe();
    const elements = useElements();
    const [msg, setMsg] = useState('');

    const handleSubmit = async (e) => {
        e.preventDefault();
        const result = await stripe.confirmCardPayment(clientSecret, {
            payment_method: { card: elements.getElement(CardElement) }
        });
        if (result.error) setMsg(result.error.message);
        else if (result.paymentIntent.status === 'succeeded') setMsg('Payment Successful!');
    };

    return (
        <form onSubmit={handleSubmit}>
            <CardElement />
            <button type="submit" disabled={!stripe}>Pay Now</button>
            {msg && <div>{msg}</div>}
        </form>
    );
};
export default PaymentForm;
