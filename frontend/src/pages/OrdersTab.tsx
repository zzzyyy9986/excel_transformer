import { observer } from 'mobx-react-lite';
import { useEffect } from 'react';
import { templateStore } from '../store/TemplateStore';

export const OrdersTab = observer(function OrdersTab() {
  useEffect(() => {
    void templateStore.loadOrders();
  }, []);

  if (templateStore.ordersLoading) {
    return <div className="text-center py-5 text-muted">Загрузка заказов…</div>;
  }

  if (templateStore.orders.length === 0) {
    return (
      <div className="alert alert-secondary mb-0">
        Заказов пока нет. Оформите заказ на вкладке «Заказ по шаблону».
      </div>
    );
  }

  return (
    <div className="orders-tab">
      <div className="d-flex justify-content-end mb-3">
        <button
          type="button"
          className="btn btn-outline-secondary btn-sm"
          onClick={() => void templateStore.loadOrders()}
          disabled={templateStore.ordersLoading}
        >
          Обновить
        </button>
      </div>

      <div className="table-responsive">
        <table className="table table-bordered table-hover align-middle mb-0">
          <thead className="table-light">
            <tr>
              <th>#</th>
              <th>Дата</th>
              <th>Прайс</th>
              <th>Как связаться</th>
              <th>Комментарий</th>
              <th className="text-end">Сумма</th>
              <th className="text-center">Позиций</th>
            </tr>
          </thead>
          <tbody>
            {templateStore.orders.map((order) => (
              <tr key={order.id}>
                <td>{order.id}</td>
                <td className="text-nowrap">{formatDate(order.created_at)}</td>
                <td>{order.tier_name}</td>
                <td>{order.client_email || '—'}</td>
                <td className="orders-tab__comment">{order.comment || '—'}</td>
                <td className="text-end text-nowrap">{Number(order.total_amount).toFixed(2)}</td>
                <td className="text-center">{order.items.length}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {templateStore.orders.map((order) => (
        <details key={order.id} className="orders-tab__details mt-3">
          <summary className="orders-tab__summary">
            Заказ #{order.id} — позиции
          </summary>
          <div className="table-responsive mt-2">
            <table className="table table-sm table-bordered mb-0">
              <thead className="table-light">
                <tr>
                  <th>Модель</th>
                  <th>Размер</th>
                  <th>Цвет</th>
                  <th className="text-center">Кол-во</th>
                  <th className="text-end">Цена</th>
                  <th className="text-end">Сумма</th>
                </tr>
              </thead>
              <tbody>
                {order.items.map((item) => (
                  <tr key={item.row_index}>
                    <td>{item.model}</td>
                    <td>{item.size}</td>
                    <td>{item.color}</td>
                    <td className="text-center">{item.quantity}</td>
                    <td className="text-end">{item.unit_price.toFixed(2)}</td>
                    <td className="text-end">{(item.quantity * item.unit_price).toFixed(2)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </details>
      ))}
    </div>
  );
});

function formatDate(iso: string): string {
  return new Date(iso).toLocaleString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}
