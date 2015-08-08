<?php
namespace Home\Model;
/**
 * ============================================================================
 * WSTMall开源商城
 * 官网地址:http://www.wstmall.com 
 * 联系QQ:707563272
 * ============================================================================
 * 商品服务类
 */
class GoodsModel extends BaseModel {
	
	/**
	 * 商品列表
	 */
	public function getGoodsList($obj){
		$areaId2 = $obj["areaId2"];
		$areaId3 = $obj["areaId3"];
		$communityId = I("communityId");
		$c1Id = I("c1Id",0);
		$c2Id = I("c2Id");
		$c3Id = I("c3Id");
		$pcurr = I("pcurr");
		$msort = I("msort",1);//排序标识
		$prices = I("prices");
		if($prices != ""){
			$pricelist = explode("_",$prices);
		}
		$brandId = I("brandId",0);
		
		$keyWords = I("keyWords");
		
		$sql = "SELECT  g.goodsId,goodsSn,goodsName,goodsThums,goodsStock,g.saleCount,p.shopId,marketPrice,shopPrice,ga.id goodsAttrId 
				FROM __PREFIX__goods g left join __PREFIX__goods_attributes ga on g.goodsId=ga.goodsId and ga.isRecomm=1, __PREFIX__shops p ";
	    if($communityId>0){
			$sql .=" , __PREFIX__shops_communitys sc ";
		}
		
		if($brandId>0){
			$sql .=" , __PREFIX__brands bd ";
		}
		$sql .= "WHERE p.areaId2 = $areaId2 AND g.shopId = p.shopId AND  g.goodsStatus=1 AND g.goodsFlag = 1 and g.isSale=1 ";
		if($communityId>0){
			$sql .= " AND sc.shopId=p.shopId AND sc.communityId = $communityId ";
		}
		if($brandId>0){
			$sql .=" AND bd.brandId=g.brandId AND g.brandId = $brandId ";
		}
		if($c1Id>0){
			$sql .= " AND g.goodsCatId1 = $c1Id";
		}
		if($c2Id>0){
			$sql .= " AND g.goodsCatId2 = $c2Id";
		}
		if($c3Id>0){
			$sql .= " AND g.goodsCatId3 = $c3Id";
		}
		
		if($areaId3>0){
			$sql .= " AND p.areaId3 = $areaId3";
		}
		if($keyWords!=""){
			$sql .= " AND g.goodsName LIKE '%$keyWords%'";
		}
		$glist = $this->query($sql);
		$shops = array();
		$maxPrice = 0;
		for($i=0;$i<count($glist);$i++){
			$goods = $glist[$i];
			if($goods["shopPrice"]>$maxPrice){
				$maxPrice = $goods["shopPrice"];
			}
		}
	    if($prices != "" && $pricelist[0]>=0 && $pricelist[1]>=0){
			$sql .= " AND (g.shopPrice BETWEEN  ".(int)$pricelist[0]." AND ".(int)$pricelist[1].") ";
		}
	   
		if($msort==1){//综合
			$sql .= " ORDER BY g.saleCount DESC ";
		}else if($msort==6){//人气
			$sql .= " ORDER BY g.saleCount DESC ";
		}else if($msort==7){//销量
			$sql .= " ORDER BY g.saleCount DESC ";
		}else if($msort==8){//价格
			$sql .= " ORDER BY g.shopPrice ASC ";
		}else if($msort==9){//价格
			$sql .= " ORDER BY g.shopPrice DESC ";
		}else if($msort==10){//好评
				
		}else if($msort==11){//上架时间
			$sql .= " ORDER BY g.saleTime DESC ";
		}

		$pages = $this->pageQuery($sql,$pcurr,30);
		$rs["maxPrice"] = $maxPrice;
		$brands = array();
		$sql = "SELECT b.brandId, b.brandName FROM wst_brands b, wst_goods_cat_brands cb WHERE b.brandId = cb.brandId AND b.brandFlag=1 ";
		if($c1Id>0){
			$sql .= " AND cb.catId = $c1Id";
		}
		$sql .= " GROUP BY b.brandId";
		$blist = $this->query($sql);
		for($i=0;$i<count($blist);$i++){
			$brand = $blist[$i];
			$brands[$brand["brandId"]] = array('brandId'=>$brand["brandId"],'brandName'=>$brand["brandName"]);
		}
		$rs["brands"] = $brands;
		$rs["pages"] = $pages;
		$gcats["goodsCatId1"] = $c1Id;
		$gcats["goodsCatId2"] = $c2Id;
		$gcats["goodsCatId3"] = $c3Id;
		$rs["goodsNav"] = self::getGoodsNav($gcats);
		return $rs;
	}
	
	
	/**
	 * 商品列表
	 */
	public function getMaxPrice($obj){
		$areaId2 = $obj["areaId2"];

		$c1Id = I("c1Id");
		$c2Id = I("c2Id");
		$c3Id = I("c3Id");
		
		$keyWords = I("keyWords");
		
		$sql = "SELECT bd.brandId,bd.brandName, goodsId,goodsSn,goodsName,goodsThums,g.saleCount,p.shopId,marketPrice,shopPrice,p.shopName 
				FROM __PREFIX__goods g , __PREFIX__brands bd, __PREFIX__shops p ";
		$sql .= "WHERE p.areaId2 = $areaId2 AND g.shopId = p.shopId AND bd.brandId=p.brandId AND  g.goodsStatus=1 AND g.goodsFlag = 1";
		
		if($c1Id>0){
			$sql .= " AND g.goodsCatId1 = $c1Id";
		}
		if($c2Id>0){
			$sql .= " AND g.goodsCatId2 = $c2Id";
		}
		if($c3Id>0){
			$sql .= " AND g.goodsCatId3 = $c3Id";
		}
		if($keyWords!=""){
			$sql .= " AND g.goodsName LIKE '%$keyWords%'";
		}
		$sql .= " ORDER BY g.saleCount DESC";
		$glist = $this->query($sql);
		
		$maxPrice = 0;
		for($i=0;$i<count($glist);$i++){
			$goods = $glist[$i];
			if($goods["shopPrice"]>$maxPrice){
				$maxPrice = $goods["shopPrice"];
			}
		}

		return $maxPrice;
	}
	

	/**
	 * 查询商品信息
	 */
	public function getGoodsDetails($obj){		
		$goodsId = $obj["goodsId"];
		$sql = "SELECT sc.catName,sc2.catName as pCatName, g.*,shop.shopName,shop.deliveryType,ga.id goodsAttrId,ga.attrPrice,ga.attrStock,
				shop.shopAtive,shop.shopTel,shop.shopAddress,shop.deliveryTime,shop.isInvoice, shop.deliveryStartMoney,g.goodsStock,shop.deliveryFreeMoney,
				shop.deliveryMoney ,g.goodsSn,shop.serviceStartTime,shop.serviceEndTime FROM __PREFIX__goods g left join __PREFIX__goods_attributes ga on g.goodsId=ga.goodsId and ga.isRecomm=1, __PREFIX__shops shop, __PREFIX__shops_cats sc 
				LEFT JOIN __PREFIX__shops_cats sc2 ON sc.parentId = sc2.catId
				WHERE g.goodsId = $goodsId AND shop.shopId=sc.shopId AND sc.catId=g.shopCatId1 AND g.shopId = shop.shopId AND g.goodsFlag = 1 ";		
		$rs = $this->query($sql);
		
		if(!empty($rs) && $rs[0]['goodsAttrId']>0){
			$rs[0]['shopPrice'] = $rs[0]['attrPrice'];
			$rs[0]['goodsStock'] = $rs[0]['attrStock'];
		}
		return $rs[0];
	}
	
	/**
	 * 获取商品信息-购物车/核对订单用
	 */
    public function getGoodsForCheck($obj){		
		$goodsId = $obj["goodsId"];
		$goodsAttrId = $obj["goodsAttrId"];
		$sql = "SELECT sc.catName,sc2.catName as pCatName, g.attrCatId,g.goodsThums,g.goodsId,g.goodsName,g.shopPrice,g.goodsStock
				,g.shopId,shop.shopName,shop.deliveryType,shop.shopAtive,shop.shopTel,shop.shopAddress,shop.deliveryTime,shop.isInvoice, 
				shop.deliveryStartMoney,g.goodsStock,shop.deliveryFreeMoney,shop.deliveryMoney ,g.goodsSn,shop.serviceStartTime,shop.serviceEndTime
				FROM __PREFIX__goods g, __PREFIX__shops shop, __PREFIX__shops_cats sc 
				LEFT JOIN __PREFIX__shops_cats sc2 ON sc.parentId = sc2.catId
				WHERE g.goodsId = $goodsId AND shop.shopId=sc.shopId AND sc.catId=g.shopCatId1 AND g.shopId = shop.shopId AND g.goodsFlag = 1 ";		
		$rs = $this->query($sql);
		if(!empty($rs) && $rs[0]['attrCatId']>0){
			$sql = "select ga.id,ga.attrPrice,ga.attrStock,a.attrName,ga.attrVal,ga.attrId from __PREFIX__attributes a,__PREFIX__goods_attributes ga
			        where a.attrId=ga.attrId and a.catId=".$rs[0]['attrCatId']." 
			        and ga.goodsId=".$rs[0]['goodsId']." and id=".$goodsAttrId;
			$priceAttrs = $this->query($sql);
			if(!empty($priceAttrs)){
				$rs[0]['attrId'] = $priceAttrs[0]['attrId'];
				$rs[0]['goodsAttrId'] = $priceAttrs[0]['id'];
				$rs[0]['attrName'] = $priceAttrs[0]['attrName'];
				$rs[0]['attrVal'] = $priceAttrs[0]['attrVal'];
				$rs[0]['shopPrice'] = $priceAttrs[0]['attrPrice'];
				$rs[0]['goodsStock'] = $priceAttrs[0]['attrStock'];
			}
		}
		return $rs[0];
	}
	/**
	 * 获取商品的属性
	 */
	public function getAttrs($obj){
		$id = $obj["goodsId"];
		$shopId = $obj["shopId"];
		$attrCatId = $obj["attrCatId"];
		$goods = array();
		//获取规格属性
		$sql = "select ga.id,ga.attrVal,ga.attrPrice,ga.attrStock,a.attrId,a.attrName,a.isPriceAttr,a.attrType,a.attrContent
		            from __PREFIX__attributes a 
		            left join __PREFIX__goods_attributes ga on ga.attrId=a.attrId and ga.goodsId=".$id." where  
					a.attrFlag=1 and a.catId=".$attrCatId." and a.shopId=".$shopId;
		$attrRs = $this->query($sql);
		if(!empty($attrRs)){
			$priceAttr = array();
			$attrs = array();
			foreach ($attrRs as $key =>$v){
				if($v['isPriceAttr']==1){
					$goods['priceAttrId'] = $v['attrId'];
					$goods['priceAttrName'] = $v['attrName'];
					$priceAttr[] = $v;
				}else{
					$attrs[] = $v;
				}
			}
			$goods['priceAttrs'] = $priceAttr;
			$goods['attrs'] = $attrs;
		}
		return $goods;
	}
	/**
	 * 获取商品相册
	 */
	public function getGoodsImgs(){
		
		$goodsId = I("goodsId");
	
		$sql = "SELECT img.* FROM __PREFIX__goods_gallerys img WHERE img.goodsId = $goodsId ";		
		$rs = $this->query($sql);
		return $rs;
		
	}
	
	
	/**
	 * 获取关联商品
	 */
	public function getRelatedGoods(){
		
		$goodsId = I("goodsId");
		$sql = "SELECT g.* FROM __PREFIX__goods g, ".DB_PRE."goods_relateds gr WHERE g.goodsId = gr.relatedGoodsId AND g.goodsStock>0 AND g.goodsStatus = 1 AND gr.goodsId =$goodsId";
		$rs = $this->query($sql);
		return $rs;
		
	}
	
	/**
	 * 获取上架中的商品
	 */
	public function queryOnSaleByPage(){
		$shopId=(int)session('WST_USER.shopId');
		$shopCatId1 = I('shopCatId1',0);
		$shopCatId2 = I('shopCatId2',0);
		$goodsName = I('goodsName');
		$sql = "select goodsId,goodsSn,goodsName,goodsImg,goodsThums,shopPrice,goodsStock,isSale,isRecomm,isHot,isBest,isNew from __PREFIX__goods where goodsFlag=1 
		     and shopId=".$shopId." and goodsStatus=1 and isSale=1 ";
		if($shopCatId1>0)$sql.=" and shopCatId1=".$shopCatId1;
		if($shopCatId2>0)$sql.=" and shopCatId2=".$shopCatId2;
		if($goodsName!='')$sql.=" and (goodsName like '%".$goodsName."%' or goodsSn like '%".$goodsName."%') ";
		$sql.=" order by goodsId desc";
		return $this->pageQuery($sql);
	}
    /**
	 * 获取下架的商品
	 */
	public function queryUnSaleByPage(){
		$shopId=(int)session('WST_USER.shopId');
		$shopCatId1 = I('shopCatId1',0);
		$shopCatId2 = I('shopCatId2',0);
		$goodsName = I('goodsName');
		$sql = "select goodsId,goodsSn,goodsName,goodsImg,goodsThums,shopPrice,goodsStock,isSale,isRecomm,isHot,isBest,isNew from __PREFIX__goods where goodsFlag=1 
		      and shopId=".$shopId." and isSale=0 ";
		if($shopCatId1>0)$sql.=" and shopCatId1=".$shopCatId1;
		if($shopCatId2>0)$sql.=" and shopCatId2=".$shopCatId2;
		if($goodsName!='')$sql.=" and (goodsName like '%".$goodsName."%' or goodsSn like '%".$goodsName."%') ";
		$sql.=" order by goodsId desc";
		return $this->pageQuery($sql);
	}
    /**
	 * 获取审核中的商品
	 */
	public function queryPenddingByPage(){
		$shopId=(int)session('WST_USER.shopId');
		$shopCatId1 = I('shopCatId1',0);
		$shopCatId2 = I('shopCatId2',0);
		$goodsName = I('goodsName');
		$sql = "select goodsId,goodsSn,goodsName,goodsImg,goodsThums,shopPrice,goodsStock,isSale,isRecomm,isHot,isBest,isNew from __PREFIX__goods where goodsFlag=1 
		     and shopId=".$shopId." and goodsStatus=0 and isSale=1 ";
		if($shopCatId1>0)$sql.=" and shopCatId1=".$shopCatId1;
		if($shopCatId2>0)$sql.=" and shopCatId2=".$shopCatId2;
		if($goodsName!='')$sql.=" and (goodsName like '%".$goodsName."%' or goodsSn like '%".$goodsName."%') ";
		$sql.=" order by goodsId desc";
		return $this->pageQuery($sql);
	}
	/**
	 * 新增商品
	 */
	public function insert(){
	 	$rd = array('status'=>-1);
	 	$id = I("id",0);
		$data = array();
		$data["goodsSn"] = I("goodsSn");
		$data["goodsName"] = I("goodsName");
		$data["goodsImg"] = I("goodsImg");
		$data["goodsThums"] = I("goodsThumbs");
		$data["shopId"] = session('WST_USER.shopId');
		$data["marketPrice"] = (float)I("marketPrice");
		$data["shopPrice"] = (float)I("shopPrice");
		$data["goodsStock"] = (int)I("goodsStock");
		$data["isBook"] = (int)I("isBook");
		$data["bookQuantity"] = (int)I("bookQuantity");
		$data["warnStock"] = (int)I("warnStock");
		$data["goodsUnit"] = I("goodsUnit");
		$data["isBest"] = (int)I("isBest");
		$data["isRecomm"] = (int)I("isRecomm");
		$data["isNew"] = (int)I("isNew");
		$data["isHot"] = (int)I("isHot");
		$data["isSale"] = (int)I("isSale");
		$data["goodsCatId1"] = (int)I("goodsCatId1");
		$data["goodsCatId2"] = (int)I("goodsCatId2");
		$data["goodsCatId3"] = (int)I("goodsCatId3");
		$data["shopCatId1"] = (int)I("shopCatId1");
		$data["shopCatId2"] = (int)I("shopCatId2");
		$data["goodsDesc"] = I("goodsDesc");
		$data["attrCatId"] = (int)I("attrCatId");
		$data["isShopRecomm"] = 0;
		$data["isIndexRecomm"] = 0;
		$data["isActivityRecomm"] = 0;
		$data["isInnerRecomm"] = 0;
		$data["goodsStatus"] = ($GLOBALS['CONFIG']['isGoodsVerify']==1)?0:1;
		$data["goodsFlag"] = 1;
		$data["createTime"] = date('Y-m-d H:i:s');
		if($this->checkEmpty($data,true)){
			$data["brandId"] = (int)I("brandId");
			$data["goodsSpec"] = I("goodsSpec");
			$m = M('goods');
			$goodsId = $m->add($data);
			if(false !== $goodsId){
				$rd['status']= 1;
				//规格属性
				if($data["attrCatId"]>0){
					$m = M('goods_attributes');
					//获取商品类型属性
					$sql = "select attrId,attrName,isPriceAttr from __PREFIX__attributes where attrFlag=1 
					       and catId=".$data["attrCatId"]." and shopId=".session('WST_USER.shopId');
					$attrRs = $m->query($sql);
					if(!empty($attrRs)){
						$priceAttrId = 0;
						foreach ($attrRs as $key =>$v){
							if($v['isPriceAttr']==1){
								$priceAttrId = $v['attrId'];
								continue;
							}else{
								$attr = array();
								$attr['shopId'] = session('WST_USER.shopId');
								$attr['goodsId'] = $goodsId;
								$attr['attrId'] = $v['attrId'];
								$attr['attrVal'] = I('attr_name_'.$v['attrId']);
								$m->add($attr);
							}
						}
						if($priceAttrId>0){
							$no = (int)I('goodsPriceNo');
							$no = $no>50?50:$no;
							$totalStock = 0;
							for ($i=0;$i<=$no;$i++){
								$name = trim(I('price_name_'.$priceAttrId."_".$i));
								if($name=='')continue;
								$attr = array();
								$attr['shopId'] = session('WST_USER.shopId');
								$attr['goodsId'] = $goodsId;
								$attr['attrId'] = $priceAttrId;
								$attr['attrVal'] = $name;
								$attr['attrPrice'] = (float)I('price_price_'.$priceAttrId."_".$i);
								$attr['isRecomm'] = (int)I('price_isRecomm_'.$priceAttrId."_".$i);
								$attr['attrStock'] = (int)I('price_stock_'.$priceAttrId."_".$i);
								$totalStock = $totalStock + (int)$attr['attrStock'];
								$m->add($attr);
							}
							//更新商品总库存
							$sql = "update __PREFIX__goods set goodsStock=".$totalStock." where goodsId=".$goodsId;
							$m->query($sql);
						}
					}
				}
				//保存相册
				$gallery = I("gallery");
				if($gallery!=''){
					$str = explode(',',$gallery);
					foreach ($str as $k => $v){
						if($v=='')continue;
						$str1 = explode('@',$v);
						$data = array();
						$data['shopId'] = session('WST_USER.shopId');
						$data['goodsId'] = $goodsId;
						$data['goodsImg'] = $str1[0];
						$data['goodsThumbs'] = $str1[1];
						$m = M('goods_gallerys');
						$m->add($data);
					}
				}
			}
		}
		return $rd;
	} 
	 
	/**
	 * 编辑商品信息
	 */
	public function edit(){
		$rd = array('status'=>-1);
	 	$goodsId = I("id",0);
	 	$shopId = (int)session('WST_USER.shopId');
	 	//加载商品信息
	 	$m = M('goods');
	 	$goods = $m->where('goodsId='.$goodsId." and shopId=".$shopId)->find();
	 	if(empty($goods))return array();
		$data = array();
		$data["goodsSn"] = I("goodsSn");
		$data["goodsName"] = I("goodsName");
		$data["goodsImg"] = I("goodsImg");
		$data["goodsThums"] = I("goodsThumbs");
		$data["marketPrice"] = (float)I("marketPrice");
		$data["shopPrice"] = (float)I("shopPrice");
		$data["goodsStock"] = (int)I("goodsStock");
		$data["isBook"] = (int)I("isBook");
		$data["bookQuantity"] = (int)I("bookQuantity");
		$data["warnStock"] = (int)I("warnStock");
		$data["goodsUnit"] = I("goodsUnit");
		$data["isBest"] = (int)I("isBest");
		$data["isRecomm"] = (int)I("isRecomm");
		$data["isNew"] = (int)I("isNew");
		$data["isHot"] = (int)I("isHot");
		$data["isSale"] = (int)I("isSale");
		$data["goodsCatId1"] = (int)I("goodsCatId1");
		$data["goodsCatId2"] = (int)I("goodsCatId2");
		$data["goodsCatId3"] = (int)I("goodsCatId3");
		$data["shopCatId1"] = (int)I("shopCatId1");
		$data["shopCatId2"] = (int)I("shopCatId2");
		$data["goodsDesc"] = I("goodsDesc");
		$data["goodsStatus"] = ($GLOBALS['CONFIG']['isGoodsVerify']['fieldValue']==1)?0:1;
		$data["attrCatId"] = (int)I("attrCatId");
		if($this->checkEmpty($data,true)){
			$data["brandId"] = (int)I("brandId");
			$data["goodsSpec"] = I("goodsSpec");
			$rs = $m->where('goodsId='.$goods['goodsId'])->save($data);
			if(false !== $rs){
				$rd['status']= 1;
			    //规格属性
				if($data["attrCatId"]>0){
					$m = M('goods_attributes');
					//删除属性记录
					$m->query("delete from __PREFIX__goods_attributes where goodsId=".$goodsId);
					//获取商品类型属性列表
					$sql = "select attrId,attrName,isPriceAttr from __PREFIX__attributes where attrFlag=1 
					       and catId=".$data["attrCatId"]." and shopId=".session('WST_USER.shopId');
					$attrRs = $m->query($sql);
					if(!empty($attrRs)){
						$priceAttrId = 0;
						foreach ($attrRs as $key =>$v){
							if($v['isPriceAttr']==1){
								$priceAttrId = $v['attrId'];
								continue;
							}else{
								//新增
								$attr = array();
								$attr['attrVal'] =  trim(I('attr_name_'.$v['attrId']));
								$attr['attrPrice'] = 0;
								$attr['attrStock'] = 0;
								$attr['shopId'] = session('WST_USER.shopId');
								$attr['goodsId'] = $goodsId;
								$attr['attrId'] = $v['attrId'];
								$m->add($attr);
							}
						}
						if($priceAttrId>0){
							$no = (int)I('goodsPriceNo');
							$no = $no>50?50:$no;
							$totalStock = 0;
							for ($i=0;$i<=$no;$i++){
								$name = trim(I('price_name_'.$priceAttrId."_".$i));
								if($name=='')continue;
								$attr = array();
								$attr['shopId'] = session('WST_USER.shopId');
								$attr['goodsId'] = $goodsId;
								$attr['attrId'] = $priceAttrId;
								$attr['attrVal'] = $name;
								$attr['attrPrice'] = (float)I('price_price_'.$priceAttrId."_".$i);
								$attr['isRecomm'] = (int)I('price_isRecomm_'.$priceAttrId."_".$i);
								$attr['attrStock'] = (int)I('price_stock_'.$priceAttrId."_".$i);
								$totalStock = $totalStock + (int)$attr['attrStock'];
								$m->add($attr);
							}
							//更新商品总库存
							$sql = "update __PREFIX__goods set goodsStock=".$totalStock." where goodsId=".$goodsId;
							$m->query($sql);
						}
					}
				}
				
			    //保存相册
				$gallery = I("gallery");
				if($gallery!=''){
					$str = explode(',',$gallery);
					$m = M('goods_gallerys');
					//删除相册信息
					$m->where('goodsId='.$goods['goodsId'])->delete();
					//保存相册信息
					foreach ($str as $k => $v){
						if($v=='')continue;
						$str1 = explode('@',$v);
						$data = array();
						$data['shopId'] = $goods['shopId'];
						$data['goodsId'] = $goods['goodsId'];
						$data['goodsImg'] = $str1[0];
						$data['goodsThumbs'] = $str1[1];
						$m->add($data);
					}
				}
			}
		}
		return $rd;
	}
	/**
	 * 获取商品信息
	 */
	 public function get(){
	 	$m = M('goods');
	 	$id = I('id',0);
	 	$shopId = (int)session('WST_USER.shopId');
		$goods = $m->where("goodsId=".$id." and shopId=".$shopId)->find();
		if(empty($goods))return array();
		$m = M('goods_gallerys');
		$goods['gallery'] = $m->where('goodsId='.$id)->select();
		//获取规格属性
		$sql = "select ga.attrVal,ga.attrPrice,ga.attrStock,a.attrId,a.attrName,a.isPriceAttr,a.attrType,a.attrContent
		            ,ga.isRecomm from __PREFIX__attributes a 
		            left join __PREFIX__goods_attributes ga on ga.attrId=a.attrId and ga.goodsId=".$id." where  
					a.attrFlag=1 and a.catId=".$goods['attrCatId']." and a.shopId=".$shopId;
		$attrRs = $m->query($sql);
		if(!empty($attrRs)){
			$priceAttr = array();
			$attrs = array();
			foreach ($attrRs as $key =>$v){
				if($v['isPriceAttr']==1){
					$goods['priceAttrId'] = $v['attrId'];
					$goods['priceAttrName'] = $v['attrName'];
					$priceAttr[] = $v;
				}else{
					//分解下拉和多选的选项
					if($v['attrType']==1 || $v['attrType']==2){
						$v['opts']['txt'] = explode(',',$v['attrContent']);
						if($v['attrType']==1){
							$vs = explode(',',$v['attrVal']);
							//保存多选的值
							foreach ($vs as $vv){
								$v['opts']['val'][$vv] = 1;
							}
						}
					}
					$attrs[] = $v;
				}
			}
			$goods['priceAttrs'] = $priceAttr;
			$goods['attrs'] = $attrs;
		}
		return $goods;
	 }
	 /**
	  * 删除商品
	  */
	 public function del(){
	 	$rd = array('status'=>-1);
	 	$m = M('goods');
	 	$shopId = (int)session('WST_USER.shopId');
	 	$data = array();
		$data["goodsFlag"] = -1;
	 	$rs = $m->where("shopId=".$shopId." and goodsId=".I('id'))->save($data);
	    if(false !== $rs){
			$rd['status']= 1;
		}
		return $rd;
	 }
	 
	 /**
	  * 批量删除商品
	  */
	 public function batchDel(){
	 	$rd = array('status'=>-1);
	 	$m = M('goods');
	 	$shopId = (int)session('WST_USER.shopId');
	 	$data = array();
		$data["goodsFlag"] = -1;
	 	$rs = $m->where("shopId=".$shopId." and goodsId in(".I('ids').")")->save($data);
	    if(false !== $rs){
			$rd['status']= 1;
		}
		return $rd;
	 }
	 /**
	  * 批量修改商品状态
	  */
	 public function goodsSet(){
	 	$rd = array('status'=>-1);
	 	$code = I('code');
	 	$codeArr = array('isBest','isNew','isHot','isRecomm');
	 	if(in_array($code,$codeArr)){
		 	$m = M('goods');
		 	$shopId = (int)session('WST_USER.shopId');
		 	$data = array();
			$data[$code] = 1;
		 	$rs = $m->where("shopId=".$shopId." and goodsId in(".I('ids').")")->save($data);
		    if(false !== $rs){
				$rd['status']= 1;
			}
	 	}
		return $rd;
	 }
     /**
	  * 批量上架/下架商品
	  */
	 public function sale(){
	 	$rd = array('status'=>-1);
	 	$m = M('goods');
	 	$isSale = (int)I('isSale');
	 	$shopId = (int)session('WST_USER.shopId');
	 	$ids = I('ids');
	 	if($isSale==1){
	 		//核对店铺状态
	 		$sql = "select shopStatus from __PREFIX__shops where shopId=".$shopId;
	 		$shopRs = $m->query($sql);
	 		if($shopRs[0]['shopStatus']!=1){
	 			$rd['status']= -3;
	 			return $rd;
	 		}
	 		//核对商品是否符合上架的条件
	 		$sql = "select g.goodsId from __PREFIX__goods g,__PREFIX__shops_cats sc2,__PREFIX__goods_cats gc3 
	 		   where g.shopCatId2=sc2.catId and sc2.catFlag=1 and sc2.isShow=1 and g.goodsCatId3=gc3.catId and gc3.catFlag=1 and gc3.isShow=1
	 		   and g.goodsId in(".$ids.")";
	 		$goodsRs = $m->query($sql);
	 		if(count($goodsRs)>0){
	 			$rd['num'] = 0;
	 			foreach ($goodsRs as $key =>$v){
			 		//商品上架操作
				 	$data = array();
					$data["isSale"] = 1;
				 	$rs = $m->where("shopId=".$shopId." and goodsId =".$v['goodsId'])->save($data);
				    if(false !== $rs){
						$rd['num']++;
					}
	 			}
	 			$rd['status'] = (count(explode(',',$ids))==$rd['num'])?1:2;
	 		}else{
	 			$rd['status']= -2;
	 		}
	 	}else{
		 	//商品下架操作
		 	$data = array();
			$data["isSale"] = 0;
		 	$rs = $m->where("shopId=".$shopId." and goodsId in(".$ids.")")->save($data);
		    if(false !== $rs){
				$rd['status']= 1;
			}
	 	}
	 	
		return $rd;
	 }
	 
	/**
	 * 获取门店商品列表
	 */
	public function getShopsGoods($shopId = 0){
		
		$shopId = ($shopId>0)?$shopId:(int)I("shopId");
		$ct1 = (int)I("ct1");
		$ct2 = (int)I("ct2");
		$msort = (int)I("msort");//排序標識		
		
		$sprice = I("sprice");//开始价格
		$eprice = I("eprice");//结束价格
		$goodsName = I("goodsName");//搜索店鋪名
		$sql = "SELECT sp.shopName, g.saleCount totalnum, sp.shopId ,g.goodsStock, g.goodsId , g.goodsName,g.goodsImg, g.goodsThums,g.shopPrice,g.marketPrice, g.goodsSn,ga.id goodsAttrId 
						FROM __PREFIX__goods g left join __PREFIX__goods_attributes ga on g.goodsId = ga.goodsId and ga.isRecomm=1,__PREFIX__shops sp 
						WHERE g.shopId = sp.shopId AND sp.shopFlag=1 AND sp.shopStatus=1 AND g.goodsFlag = 1 AND g.isSale = 1 AND g.goodsStatus = 1 AND g.shopId = $shopId";
		
		if($ct1>0){
			$sql .= " AND g.shopCatId1 = $ct1 ";
		}
		if($ct2>0){
			$sql .= " AND g.shopCatId2 = $ct2 ";
		}
		if($sprice!=""){
			$sql .= " AND g.shopPrice >= '$sprice' ";
		}
		if($eprice!=""){
			$sql .= " AND g.shopPrice <= '$eprice' ";
		}
		if($goodsName!=""){
			$sql .= " AND g.goodsName like '%$goodsName%' ";
		}
		if($msort==1){//综合
			$sql .= " ORDER BY g.saleCount DESC ";
		}else if($msort==2){//人气
			$sql .= " ORDER BY g.saleCount DESC ";
		}else if($msort==3){//销量
			$sql .= " ORDER BY g.saleCount DESC ";
		}else if($msort==4){//价格
			$sql .= " ORDER BY g.shopPrice ASC ";
		}else if($msort==5){//价格
			$sql .= " ORDER BY g.shopPrice DESC ";
		}else if($msort==6){//好评
			
		}else if($msort==7){//上架时间
			$sql .= " ORDER BY g.saleTime DESC ";
		}
		$rs = $this->query($sql);
		return $rs;
		
	}
	
	
	/**
	 * 获取门店商品列表
	 */
	public function getHotGoods($shopId){
		//热销排名
		$sql = "SELECT sp.shopName, g.saleCount totalnum, sp.shopId , g.goodsId , g.goodsName,g.goodsImg, g.goodsThums,g.shopPrice,g.marketPrice, g.goodsSn 
						FROM __PREFIX__goods g,__PREFIX__shops sp 
						WHERE g.shopId = sp.shopId AND g.goodsFlag = 1 AND sp.shopFlag=1 AND sp.shopStatus=1 AND g.isAdminBest = 1 AND g.isSale = 1 AND g.goodsStatus = 1 AND sp.shopId = $shopId
						ORDER BY g.saleCount desc limit 5";
				
		$hotgoods = $this->query($sql);
		return  $hotgoods;
	}
	
	/**
	 * 获取商品库存
	 */
	public function getGoodsStock($data){
	 	$goodsId = $data['goodsId'];
		$isBook = $data['isBook'];
		$goodsAttrId = $data['goodsAttrId'];
		if($isBook==1){
			$sql = "select goodsId,(goodsStock+bookQuantity) as goodsStock from __PREFIX__goods where isSale=1 and goodsFlag=1 and goodsStatus=1 and goodsId=".$goodsId;
		}else{
			$sql = "select goodsId,goodsStock,attrCatId from __PREFIX__goods where isSale=1 and goodsFlag=1 and goodsStatus=1 and goodsId=".$goodsId;
		}
	 	$goods = $this->query($sql);
	 	if($goods[0]['attrCatId']>0){
	 		$sql = "select ga.id,ga.attrStock from __PREFIX__goods_attributes ga where ga.goodsId=".$goodsId." and id=".$goodsAttrId;
			$priceAttrs = $this->query($sql);
			if(!empty($priceAttrs))$goods[0]['goodsStock'] = $priceAttrs[0]['attrStock'];
	 	}
	 	if(empty($goods))return array();
	 	return $goods[0];
	 }
	 
	 
	/**
	 * 查询商品简单信息
	 */
	public function getGoodsInfo($goodsId,$goodsAttrId){		
		$sql = "SELECT g.attrCatId,g.goodsId,g.goodsName,g.goodsStock,g.bookQuantity,g.isBook,g.isSale FROM __PREFIX__goods g WHERE g.goodsId = $goodsId AND g.goodsFlag = 1 AND g.goodsStatus = 1";		
		$rs = $this->queryRow($sql);
        if(!empty($rs) && $rs['attrCatId']>0){
        	$sql = "select ga.id,ga.attrPrice,ga.attrStock,a.attrName,ga.attrVal,ga.attrId from __PREFIX__attributes a,__PREFIX__goods_attributes ga
			        where a.attrId=ga.attrId and a.catId=".$rs['attrCatId']." 
			        and ga.goodsId=".$rs['goodsId']." and id=".$goodsAttrId;
			$priceAttrs = $this->query($sql);
			if(!empty($priceAttrs))$rs['goodsStock'] = $priceAttrs[0]['attrStock'];
        }
		return $rs;
		
	}
	
	/**
	 * 查询商品简单信息
	 */
	public function getGoodsSimpInfo($goodsId,$goodsAttrId){
		$sql = "SELECT g.*,sp.shopId,sp.shopName,sp.deliveryFreeMoney,sp.deliveryMoney,sp.deliveryStartMoney,sp.isInvoice,sp.serviceStartTime startTime,sp.serviceEndTime endTime,sp.deliveryType 
				FROM __PREFIX__goods g, __PREFIX__shops sp 
				WHERE g.shopId = sp.shopId AND g.goodsId = $goodsId AND g.isSale=1 AND g.goodsFlag = 1 AND g.goodsStatus = 1";
		$rs = $this->queryRow($sql);
	    if(!empty($rs) && $rs['attrCatId']>0){
        	$sql = "select ga.id,ga.attrPrice,ga.attrStock,a.attrName,ga.attrVal,ga.attrId from __PREFIX__attributes a,__PREFIX__goods_attributes ga
			        where a.attrId=ga.attrId and a.catId=".$rs['attrCatId']." 
			        and ga.goodsId=".$rs['goodsId']." and id=".$goodsAttrId;
			$priceAttrs = $this->query($sql);
			if(!empty($priceAttrs)){
				$rs['attrId'] = $priceAttrs[0]['attrId'];
				$rs['goodsAttrId'] = $priceAttrs[0]['id'];
				$rs['attrName'] = $priceAttrs[0]['attrName'];
				$rs['attrVal'] = $priceAttrs[0]['attrVal'];
				$rs['shopPrice'] = $priceAttrs[0]['attrPrice'];
				$rs['goodsStock'] = $priceAttrs[0]['attrStock'];
			}
        }
		return $rs;
		
	}
	

	/**
	 * 查询商品评价
	 */
	public function getGoodsAppraises(){		
		$goodsId = I("goodsId");
		$pcurr = I("pcurr",0);
		$pageSize = 20;
		$sql = "SELECT ga.*, u.userName,u.loginName, od.createTime as ocreateTIme 
				FROM __PREFIX__goods_appraises ga , __PREFIX__orders od , __PREFIX__users u 
				WHERE ga.userId = u.userId AND ga.orderId = od.orderId AND ga.goodsId = $goodsId AND ga.isShow =1";		
		$data = $this->pageQuery($sql,$pcurr,$pageSize);	
		return $data;

	}
	
	/**
	 * 获取商品类别导航
	 */
	public function getGoodsNav($obj=array()){
		$goodsId = (int)I("goodsId");
		if($goodsId>0){
			$sql = "SELECT goodsCatId1,goodsCatId2,goodsCatId3 FROM __PREFIX__goods WHERE goodsId = $goodsId";
			$rs = $this->queryRow($sql);
		}else{
			$rs = $obj;
		}
		$gclist = M('goods_cats')->cache('WST_CACHE_GOODS_CAT_URL',31536000)->where('isShow = 1')->field('catId,catName')->order('catId')->select();
		$catslist = array();
		foreach ($gclist as $key => $gcat) {
			$catslist[$gcat["catId"]] = $gcat;
		}
		
		$data[] = $catslist[$rs["goodsCatId1"]];
		$data[] = $catslist[$rs["goodsCatId2"]];
		$data[] = $catslist[$rs["goodsCatId3"]];
		return $data;
	}
	
	/**
	 * 查询商品属性价格及库存
	 */
	public function getPriceAttrInfo(){
		$goodsId = (int)I("goodsId");
		$id = (int)I("id");
		$sql = "select id,attrPrice,attrStock from  __PREFIX__goods_attributes where goodsId=".$goodsId." and id=".$id;
		$rs = $this->query($sql);
		return $rs[0];
	}
}